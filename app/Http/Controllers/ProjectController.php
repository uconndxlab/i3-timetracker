<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function create()
    {
        return redirect()->route('landing', ['view' => 'admin']);
    }

    public function update(Request $request, Project $project)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'active' => 'required|boolean',
        ]);

        $project->update($validatedData);
        return redirect()->route('landing')->with('message', 'Project updated successfully!');
    }

    public function show(Project $project, Request $request)
    {
        return redirect()->route('landing');
    }

    // public function delete(Project $project) 
    // {
    //     #$project->shifts()->delete(); do we need to delete shifts associated with the project?
    //     $project->delete();
    //     return redirect()->route('projects.index')->with('message', 'Project deleted successfully!');
    // }

    public function index(Request $request)
    {
        return redirect()->route('landing');
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'active' => 'required|boolean',
            'assign_all_users' => 'sometimes|boolean',
        ]);

        $project = Project::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'active' => $validatedData['active'],
        ]);

        if ($request->input('assign_all_users') == '1') {
            $allUsers = User::all();
            $syncData = [];
            
            foreach ($allUsers as $user) {
                $syncData[$user->netid] = ['active' => true];
            }
        
            $project->users()->sync($syncData);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Project created successfully!',
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'redirect_url' => route('landing', ['view' => 'admin']),
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('landing', ['view' => 'admin'])->with('message', 'Project created successfully!');
    }

    // public function edit(Project $project)
    // {
    //     return view('projects.edit', compact('project'));
    // }

    public function manage(Request $request)
    {
        return redirect()->route('landing');
    }

    public function syncMemberships(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'project_ids' => 'present|array',
            'project_ids.*' => 'integer|exists:projects,id',
        ]);

        $desiredIds = Project::query()
            ->where('active', true)
            ->whereIn('id', $validated['project_ids'])
            ->pluck('id');

        $currentIds = $user->projects()
            ->where('projects.active', true)
            ->pluck('projects.id');

        $toJoin = $desiredIds->diff($currentIds);
        $toLeave = $currentIds->diff($desiredIds);

        foreach ($toJoin as $projectId) {
            Project::find($projectId)?->users()->syncWithoutDetaching([
                $user->netid => ['active' => true],
            ]);
        }

        foreach ($toLeave as $projectId) {
            $user->projects()->detach($projectId);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Projects updated successfully.',
                'joined' => $toJoin->count(),
                'left' => $toLeave->count(),
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('landing')->with('message', 'Projects updated successfully.');
    }

    public function join(Request $request, Project $project)
    {
        $user = auth()->user();
        
        if (!$user->projects->contains($project->id)) {
            $project->users()->attach($user->netid, ['active' => true]);
            return $this->redirectManage($request, 'Successfully joined ' . $project->name);
        }

        return $this->redirectManage($request, 'You are already a member of ' . $project->name);
    }

    public function leave(Request $request, Project $project)
    {
        $user = auth()->user();
        
        if ($user->projects->contains($project->id)) {
            $project->users()->detach($user->netid);
            return $this->redirectManage($request, 'Successfully left ' . $project->name);
        }

        return $this->redirectManage($request, 'You are not a member of ' . $project->name);
    }

    private function redirectManage(Request $request, string $message)
    {
        return redirect()
            ->route('landing')
            ->with('message', $message);
    }

}