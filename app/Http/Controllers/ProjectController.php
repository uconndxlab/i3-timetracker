<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
            'active' => 'required|boolean',
        ]);

        $project->update($validatedData);
        return redirect()->route('projects.index')->with('message', 'Project updated successfully!');
    }

    // public function show(Project $project) 
    // {
    //     $user = auth()->user();
    //     if (!$user->isAdmin()) {
    //         $isAssigned = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
    //             ->where('project_user.user_netid', $user->netid)
    //             ->where('projects.id', $project->id)
    //             ->exists();
                
    //         if (!$isAssigned) {
    //             abort(403, 'You do not have access to this project.');
    //         }
    //     }
        
    //     $project->load('shifts.user');
    //     return view('projects.show', compact('project'));
    // }

    public function delete(Project $project) 
    {
        #$project->shifts()->delete(); do we need to delete shifts associated with the project?
        $project->delete();
        return redirect()->route('projects.index')->with('message', 'Project deleted successfully!');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');
        $query = Project::query();
        
        if (!$user->isAdmin()) {
            $query->join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->select('projects.*')
                ->distinct();
        }
        
        if ($sortField === 'assigned_users_count' || $sortField === 'billed_hours' || $sortField === 'unbilled_hours') {
            $allProjects = $query->get();
            foreach ($allProjects as $project) {
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
            
            $allProjects = $allProjects->sortBy($sortField, SORT_REGULAR, $direction === 'desc');
            $page = $request->input('page', 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            $projects = new LengthAwarePaginator(
                $allProjects->slice($offset, $perPage),
                $allProjects->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            return view('projects.index', compact('projects'));
        }
        else if ($sortField) {
            $query->orderBy($sortField, $direction);
        } 
        else {
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
            'active' => 'required|boolean',
        ]);
        Project::create($validatedData);
        return redirect()->route('projects.index')->with('message', 'Project created successfully!');
    }

    // public function edit(Project $project)
    // {
    //     return view('projects.edit', compact('project'));
    // }

}