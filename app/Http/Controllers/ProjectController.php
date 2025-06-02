<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'desc' => 'nullable|string',
            'active' => 'boolean',
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'desc' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $project->update($validatedData);
    }

    public function show(Project $project) 
    {
        # Get the project details and return to a view.
    }

    public function delete() 
    {
        #$project->shifts()->delete(); do we need to delete shifts associated with the project?
        $project->delete();
        # add redirect with success message
    }
}