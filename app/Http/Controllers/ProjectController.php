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
        $projects = Project::latest()->get();
        return view('projects.index', compact('projects'));
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('active');
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

    public function landing()
    {
        $activeProjects = Project::where('active', true)->latest()->get();
        return view('landing', compact('activeProjects'));
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }
}