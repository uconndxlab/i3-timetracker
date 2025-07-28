<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function create()
    {
        $user = auth()->user();
        
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
        
        return view('shifts.create', compact('projects'));
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
        return redirect()->route('shifts.show', $shift)->with('message', 'Shift updated successfully!');
    }


    public function show(Shift $shift)
    {
        $shift->load(['user', 'project']);
        return view('shifts.show', compact('shift'));
    }

    public function index()
    {
        $shifts = Shift::with(['user', 'project'])->latest()->get();
        return view('shifts.index', compact('shifts'));
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
            // Use the same approach that works in the create method
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
        return redirect()->route('shifts.index')->with('message', 'Shift created successfully!');
    }
}
