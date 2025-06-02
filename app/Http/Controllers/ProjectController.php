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

    public function update() 
    {

    }

    public function show() 
    {

    }

    public function delete() 
    {

    }
}