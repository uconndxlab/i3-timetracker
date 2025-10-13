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
            'description' => 'nullable|string|max:1000',
            'active' => 'required|boolean',
        ]);

        $project->update($validatedData);
        return redirect()->route('projects.index')->with('message', 'Project updated successfully!');
    }

    public function show(Project $project, Request $request) 
    {
        $user = auth()->user();
        $sortField = $request->input('sort', 'start_time');
        $direction = $request->input('direction', 'desc');
        
        $shifts = $project->shifts()
            ->with('user') 
            ->orderBy($sortField, $direction)
            ->paginate(10);
        
        foreach ($shifts as $shift) {
            $shift->time_range = $shift->start_time->format('M d, Y H:i') . ' - ' . 
                            $shift->end_time->format('M d, Y H:i');

            $duration = $shift->start_time->diffInMinutes($shift->end_time) / 60;
            $shift->duration = number_format($duration, 2) . ' hrs';
            $shift->user_name = $shift->user ? $shift->user->name : 'N/A';
        }
        
        $shiftColumns = [
            ['key' => 'time_range', 'label' => 'Date', 'sortable' => false],
            ['key' => 'user_name', 'label' => 'Name', 'sortable' => false],
            ['key' => 'shift_time', 'label' => 'Hours', 'sortable' => false],
            ['key' => 'duration', 'label' => 'Duration', 'sortable' => true],
            ['key' => 'entered', 'label' => 'Entered (Timecard)', 'sortable' => true, 'type' => 'boolean'],
            ['key' => 'billed', 'label' => 'Billed (Honeycrisp)', 'sortable' => true, 'type' => 'boolean'],
        ];
        
        $shiftActions = [
            ['key' => 'edit', 'label' => 'Edit Shift', 'icon' => 'pencil-square', 'route' => 'shifts.edit'],
        ];
        if ($user->isAdmin()) {
            $shiftActions[] = ['key' => 'delete', 'label' => 'Delete Shift', 'icon' => 'trash', 'route' => 'shifts.destroy', 'method' => 'DELETE', 'confirm' => 'Are you sure you want to delete this shift?'];
        }
        $totalHours = 0;
        $billedHours = 0;
        $unbilledHours = 0;
        
        foreach ($project->shifts as $shift) {
            $shift->time_range = $shift->start_time->format('M d, Y');
            $shift->shift_time = $shift->start_time->format('g:i A') . ' - ' . $shift->end_time->format('g:i A');
            
            $duration = $shift->start_time->diffInMinutes($shift->end_time) / 60;
            $shift->duration = number_format($duration, 2) . ' hrs';

            $hours = $shift->start_time->diffInMinutes($shift->end_time) / 60;
            $totalHours += $hours;
            
            if ($shift->billed) {
                $billedHours += $hours;
            } else {
                $unbilledHours += $hours;
            }
        }
        
        return view('projects.show', compact(
            'project',
            'shifts',
            'shiftColumns',
            'shiftActions',
            'totalHours',
            'billedHours',
            'unbilledHours'
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
        else if ($sortField === 'name') {
            $allProjects = $query->get();
            $allProjects = $allProjects->sortBy(function($project) {
                return strtolower($project->name);
            }, SORT_STRING, $direction === 'desc');
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
        }

        else if ($sortField) {
            $query->orderBy($sortField, $direction);
            $projects = $query->paginate(10);
        } 

        else {
            $allProjects = $query->get()->sortBy(function($project) {
                return strtolower($project->name);
            });

            $page = $request->input('page', 1);
            $perPage = 10;
            $projects = new LengthAwarePaginator(
                $allProjects->slice(($page - 1) * $perPage, $perPage),
                $allProjects->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }
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
            'description' => 'nullable|string|max:1000',
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