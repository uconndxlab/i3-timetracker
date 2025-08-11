<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function create()
    {
        return view('projects.create');
    }

    public function update(Request $request, Project $project)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'desc' => 'nullable|string',
            'active' => 'sometimes|required|boolean',
        ]);

        $project->update($validatedData);
        return redirect()->route('projects.show', $project)->with('message', 'Project updated successfully!');
    }

    public function show(Project $project) 
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            $isAssigned = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->where('projects.id', $project->id)
                ->exists();
                
            if (!$isAssigned) {
                abort(403, 'You do not have access to this project.');
            }
        }
        
        $project->load('shifts.user');
        return view('projects.show', compact('project'));
    }

    public function delete(Project $project) 
    {
        #$project->shifts()->delete(); do we need to delete shifts associated with the project?
        $project->delete();
        return redirect()->route('projects.index')->with('message', 'Project deleted successfully!');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Project::query();
        
        if (!$user->isAdmin()) {
            $query->join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->select('projects.*');
        }
        
        if ($request->has('sort')) {
            $direction = $request->input('direction', 'asc');
            $query->orderBy($request->input('sort'), $direction);
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $projects = $query->paginate(10);
        foreach ($projects as $project) {
            $project->assigned_users_count = $project->users()->count();
            
            $billedShifts = $project->shifts()->where('billed', true)->get();
            $billedHours = 0;
            foreach ($billedShifts as $shift) {
                $billedHours += $shift->start_time->diffInHours($shift->end_time);
            }
            $project->billed_hours = $billedHours;
            
            $unbilledShifts = $project->shifts()->where('billed', false)->get();
            $unbilledHours = 0;
            foreach ($unbilledShifts as $shift) {
                $unbilledHours += $shift->start_time->diffInHours($shift->end_time);
            }
            $project->unbilled_hours = $unbilledHours;
        }
        
        return view('projects.index', compact('projects'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'active' => 'required|boolean',
        ]);
        Project::create($validatedData);
        return redirect()->route('projects.index')->with('message', 'Project created successfully!');
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

}