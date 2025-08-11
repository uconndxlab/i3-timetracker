<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();
        $selectedProjectId = $request->input('proj_id');
        $selectedProject = null;
        
        if ($selectedProjectId) {
            $selectedProject = Project::find($selectedProjectId);

            if ($selectedProject && !$user->isAdmin()) {
                $hasAccess = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                    ->where('project_user.user_netid', $user->netid)
                    ->where('projects.id', $selectedProjectId)
                    ->exists();
                    
                if (!$hasAccess) {
                    $selectedProject = null; 
                }
            }
        }
        if ($user->isAdmin()) {
            $projects = Project::where('active', true)->get();
        } else {
            $projectIds = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->pluck('projects.id');
            $projects = Project::whereIn('id', $projectIds)
                ->where('active', true)
                ->get();
        }
        
        return view('shifts.create', compact('projects', 'selectedProject'));
    }

    public function update(Request $request, Shift $shift)
    {
        $validatedData = $request->validate([
            'netid' => 'sometimes|required|exists:users,netid',
            'proj_id' => 'sometimes|required|exists:projects,id',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'entered' => 'sometimes|required|boolean',
            'billed' => 'sometimes|required|boolean',
        ], [], [
            'netid' => 'Name',
            'proj_id' => 'Project',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'entered' => 'Entered in University System',
            'billed' => 'Billed in Cider',
        ]);

        $shift->update($validatedData);
        return redirect()->route('shifts.index')->with('message', 'Shift updated successfully!');
    }


    // public function show(Shift $shift)
    // {
    //     $shift->load(['user', 'project']);
    //     return view('shifts.show', compact('shift'));
    // }

    public function index(Request $request)
    {
        $user = auth()->user();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        
        if ($request->has('week')) {
            $weekOffset = (int)$request->input('week');
            $weekStart = now()->startOfWeek()->addWeeks($weekOffset);
            $weekEnd = now()->endOfWeek()->addWeeks($weekOffset);
        }
        
        $query = Shift::query()
            ->with(['project', 'user'])
            ->whereBetween('start_time', [$weekStart, $weekEnd]);
        
        if (!$user->isAdmin()) {
            $query->where('netid', $user->netid);
        }
        
        if ($request->has('sort')) {
            $direction = $request->input('direction', 'asc');
            if ($request->input('sort') === 'project') {
                $query->join('projects', 'shifts.proj_id', '=', 'projects.id')
                    ->orderBy('projects.name', $direction)
                    ->select('shifts.*');
            } else {
                $query->orderBy($request->input('sort'), $direction);
            }
        } else {
            $query->orderBy('start_time', 'desc');
        }
        
        $shifts = $query->paginate(15);
        foreach ($shifts as $shift) {
            $shift->time_range = $shift->start_time->format('g A') . ' - ' . $shift->end_time->format('g A');
            $durationMinutes = $shift->start_time->diffInMinutes($shift->end_time);
            $shift->duration = round($durationMinutes / 60, 1);
            $shift->can_edit = $user->isAdmin() || 
                ($shift->netid === $user->netid && !$shift->entered && !$shift->billed);
        }

        $currOffset = (int)$request->input('week', 0);
        $prev = $currOffset - 1;
        $next = $currOffset + 1;
        $start = $weekStart->format('M j, Y');
        $end = $weekEnd->format('M j, Y');
        
        return view('shifts.index', compact(
            'shifts', 
            'start', 
            'end', 
            'prev', 
            'next', 
            'currOffset'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $validatedData = $request->validate([
            'netid' => 'required|exists:users,netid',
            'proj_id' => 'required|exists:projects,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'entered' => 'required|boolean',
        ], [], [
            'netid' => 'Name',
            'proj_id' => 'Project',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'entered' => 'Entered in University System',
        ]);

        if (!$user->isAdmin()) {
            $projectIds = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->where('projects.active', true)
                ->pluck('projects.id')
                ->toArray();
                
            if (!in_array($validatedData['proj_id'], $projectIds)) {
                return back()->withErrors(['proj_id' => 'You are not authorized to log shifts for this project.']);
            }
        }

        if (!isset($validatedData['billed'])) {
            $validatedData['billed'] = false;
        }

        $user = User::where('netid', $validatedData['netid'])->first();
        $project = Project::find($validatedData['proj_id']);

        $project->users()->syncWithoutDetaching([$user->netid]);

        Shift::create($validatedData);
        return redirect()->route('shifts.index')->with('message', 'Shift logged successfully!');
    }

    public function edit(Shift $shift)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && 
            ($shift->netid !== $user->netid || $shift->entered || $shift->billed)) {
            return redirect()->route('shifts.index')->with('message', 'You cannot edit this shift.');
        }
        
        if ($user->isAdmin()) {
            $projects = Project::where('active', true)->get();
        } else {
            $projectIds = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->pluck('projects.id');
            $projects = Project::whereIn('id', $projectIds)
                ->where('active', true)
                ->get();
        }
        
        return view('shifts.edit', compact('shift', 'projects'));
    }
        
    public function destroy(Shift $shift)
    {
        $user = auth()->user();
        
        if (!$user->isAdmin() && 
            ($shift->netid !== $user->netid || $shift->entered || $shift->billed)) {
            abort(403, 'You cannot delete this shift.');
        }
        
        $shift->delete();
        
        return redirect()->route('shifts.index')->with('message', 'Shift deleted successfully.');
    }
}
