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
        $validatedData = $request->validate([
            'netid' => 'required|exists:users,id',
            'proj_id' => 'required|exists:projects,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'billed' => 'required|boolean',
        ]);

        Shift::create($validatedData);
    }

    public function update(Request $request, Shift $shift)
    {
        $validatedData = $request->validate([
            'netid' => 'sometimes|exists:users,id',
            'proj_id' => 'sometimes|exists:projects,id',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'billed' => 'sometimes|boolean',
        ]);

        $shift->update($validatedData);
    }

    
    public function show(Shift $shift)
    {
        # Get the shift details and return to a view.
    }
}
