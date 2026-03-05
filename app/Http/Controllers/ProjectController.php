<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
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
            'description' => 'nullable|string|max:1000',
            'active' => 'required|boolean',
        ]);

        $project->update($validatedData);
        return redirect()->route('projects.index')->with('message', 'Project updated successfully!');
    }

    public function show(Project $project, Request $request) 
    {
        $user = auth()->user();
        $sortField = $request->input('sort', 'date');
        $direction = $request->input('direction', 'desc');
    
        $shiftsQuery = $project->shifts()->with('user');
        
        if (!$user->isAdmin()) {
            $shiftsQuery->where('netid', $user->netid);
        }
        
        $shifts = $shiftsQuery
            ->orderBy($sortField, $direction)->get();
        
        foreach ($shifts as $shift) {
            $shift->time_range = $shift->date->format('M d, Y');
            $shift->user_name = $shift->user ? $shift->user->name : 'N/A';
        }
        
        $shiftColumns = [
            ['key' => 'time_range', 'label' => 'Date', 'sortable' => false],
            ['key' => 'user_name', 'label' => 'Name', 'sortable' => false],
            ['key' => 'duration', 'label' => 'Duration', 'sortable' => true, 'type' => 'duration'],
            ['key' => 'entered', 'label' => 'Entered (Timecard)', 'sortable' => true, 'type' => 'boolean'],
            ['key' => 'billed', 'label' => 'Billed (Honeycrisp)', 'sortable' => true, 'type' => 'boolean'],
        ];
        
        $shiftActions = [
            ['key' => 'edit', 'label' => 'Edit Shift', 'icon' => 'pencil-square', 'route' => 'shifts.edit'],
        ];
        if ($user->isAdmin()) {
            $shiftActions[] = ['key' => 'delete', 'label' => 'Delete Shift', 'icon' => 'trash', 'route' => 'shifts.destroy', 'method' => 'DELETE', 'confirm' => 'Are you sure you want to delete this shift?'];
        }

        $hours = $user->isAdmin() 
            ? $project->getAllHours() 
            : $project->getHoursForUser($user->netid);
        
        $totalHours = $hours['total_hours'];
        $billedHours = $hours['billed_hours'];
        $unbilledHours = $hours['unbilled_hours'];
        
        $unbilledShiftCount = $user->isAdmin() 
            ? $project->shifts()->where('billed', false)->count() 
            : $project->shifts()->where('netid', $user->netid)->where('billed', false)->count();
        
        $description = $project->description ?: 'N/A';
        
        return view('projects.show', compact(
            'project',
            'description',
            'shifts',
            'shiftColumns',
            'shiftActions',
            'totalHours',
            'billedHours',
            'unbilledHours',
            'unbilledShiftCount'
        ));
    }

    // public function delete(Project $project) 
    // {
    //     #$project->shifts()->delete(); do we need to delete shifts associated with the project?
    //     $project->delete();
    //     return redirect()->route('projects.index')->with('message', 'Project deleted successfully!');
    // }

    public function index(Request $request)
    {
        $user = auth()->user();
        $sortField = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        
        $query = Project::query()->withCount('users');
        
        $projects = $query->get();
        $userProjectIds = $user->projects->pluck('id')->toArray();
        
        $user_assigned_projects = $projects->filter(function($project) use ($userProjectIds) {
            return in_array($project->id, $userProjectIds, true);
        });
        $non_user_assigned_projects = $projects->filter(function($project) use ($userProjectIds) {
            return !in_array($project->id, $userProjectIds, true);
        });
        
        $projects = $user_assigned_projects->merge($non_user_assigned_projects);
        foreach ($projects as $project) {
            $project->assigned_users_count = $project->users_count;
            
            $hours = $project->getAllHours();
            $project->billed_hours = $hours['billed_hours'];
            $project->unbilled_hours = $hours['unbilled_hours'];
            $project->is_user_assigned = in_array($project->id, $userProjectIds, true);
        }

        if ($request->has('sort')) {
            if ($sortField === 'name') {
                $projects = $this->sortProjectsByName($projects, $direction === 'desc');
            } 
            else {
                $projects = $projects->sortBy($sortField, SORT_REGULAR, $direction === 'desc');
            }
        } 
        else {
            $user_assigned_projects = $this->sortProjectsByName($user_assigned_projects)->values();
            $non_user_assigned_projects = $this->sortProjectsByName($non_user_assigned_projects)->values();
            $projects = $user_assigned_projects->merge($non_user_assigned_projects);
        }
        
        return view('projects.index', compact('projects', 'user_assigned_projects', 'non_user_assigned_projects'));
    }

    public function store(Request $request)
    {
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

        return redirect()->route('projects.index')->with('message', 'Project created successfully!');
    }

    // public function edit(Project $project)
    // {
    //     return view('projects.edit', compact('project'));
    // }

    public function manage(Request $request)
    {
        $user = auth()->user();
        $query = Project::query();
        
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }
        
        $projects = $query->orderBy('name')->paginate(20)->withQueryString();
        
        $userProjectIds = $user->projects->pluck('id')->toArray();
        
        return view('projects.manage', compact('projects', 'userProjectIds'));
    }

    public function join(Request $request, Project $project)
    {
        $user = auth()->user();
        
        if (!$user->projects->contains($project->id)) {
            $project->users()->attach($user->netid, ['active' => true]);
            return $this->redirectToManageWithMessage($request, 'Successfully joined ' . $project->name);
        }

        return $this->redirectToManageWithMessage($request, 'You are already a member of ' . $project->name);
    }

    public function leave(Request $request, Project $project)
    {
        $user = auth()->user();
        
        if ($user->projects->contains($project->id)) {
            $project->users()->detach($user->netid);
            return $this->redirectToManageWithMessage($request, 'Successfully left ' . $project->name);
        }

        return $this->redirectToManageWithMessage($request, 'You are not a member of ' . $project->name);
    }

    private function manageSearchParams(Request $request): array
    {
        if ($request->has('search') && $request->search) {
            return ['search' => $request->search];
        }

        return [];
    }

    private function redirectToManageWithMessage(Request $request, string $message)
    {
        return redirect()
            ->route('projects.manage', $this->manageSearchParams($request))
            ->with('message', $message);
    }

    private function sortProjectsByName($projects, bool $descending = false)
    {
        return $projects->sortBy(function ($project) {
            return strtolower($project->name);
        }, SORT_STRING, $descending);
    }

}