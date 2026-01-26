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
        
        return view('projects.show', compact(
            'project',
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
        
        $query = Project::query();
        
        // if (!$user->isAdmin()) {
        //     $query->assignedToUser($user->netid);
        // }
        
        $projects = $query->get();
        
        foreach ($projects as $project) {
            $project->assigned_users_count = $project->users()->count();
            
            $hours = $project->getAllHours();
            $project->billed_hours = $hours['billed_hours'];
            $project->unbilled_hours = $hours['unbilled_hours'];
        }
        
        if ($sortField === 'name') {
            $projects = $projects->sortBy(function($project) {
                return strtolower($project->name);
            }, SORT_STRING, $direction === 'desc');
        } 
        else {
            $projects = $projects->sortBy($sortField, SORT_REGULAR, $direction === 'desc');
        }
        
        return view('projects.index', compact('projects'));
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
            $params = [];
            if ($request->has('search') && $request->search) {
                $params['search'] = $request->search;
            }
            return redirect()->route('projects.manage', $params)->with('message', 'Successfully joined ' . $project->name);
        }
        
        $params = [];
        if ($request->has('search') && $request->search) {
            $params['search'] = $request->search;
        }
        return redirect()->route('projects.manage', $params)->with('message', 'You are already a member of ' . $project->name);
    }

    public function leave(Request $request, Project $project)
    {
        $user = auth()->user();
        
        if ($user->projects->contains($project->id)) {
            $project->users()->detach($user->netid);
            $params = [];
            if ($request->has('search') && $request->search) {
                $params['search'] = $request->search;
            }
            return redirect()->route('projects.manage', $params)->with('message', 'Successfully left ' . $project->name);
        }
        
        $params = [];
        if ($request->has('search') && $request->search) {
            $params['search'] = $request->search;
        }
        return redirect()->route('projects.manage', $params)->with('message', 'You are not a member of ' . $project->name);
    }

}