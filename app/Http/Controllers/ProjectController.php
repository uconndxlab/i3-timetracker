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
        $project->load('shifts.user');
        return view('projects.show', compact('project'));
    }

    public function delete(Project $project) 
    {
        #$project->shifts()->delete(); do we need to delete shifts associated with the project?
        $project->delete();
        return redirect()->route('projects.index')->with('message', 'Project deleted successfully!');
    }

    public function index()
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            $projects = Project::where('active', true)->get();
        } else {
            $projects = $user->projects()->where('active', true)->get();
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