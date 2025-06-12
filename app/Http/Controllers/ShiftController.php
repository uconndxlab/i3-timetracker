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
        $users = User::where('active', true)->orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        return view('shifts.create', compact('users', 'projects'));
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

        if (!isset($validatedData['billed'])) {
            $validatedData['billed'] = false;
        }

        Shift::create($validatedData);
        return redirect()->route('shifts.index')->with('message', 'Shift created successfully!');
    }
}
