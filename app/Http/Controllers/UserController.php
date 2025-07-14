<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create()
    {
        return view('users.create');
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'netid' => 'sometimes|required|string|max:255|unique:users,netid,' . $user->netid,
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'active' => 'sometimes|boolean',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']); 
        }
        
        $user->update($validatedData);
        // "return redirect()->route('users.show', $user)->with('message', 'User updated successfully!');" // make view
    }

    
    public function show(User $user)
    {
        $user->load(['shifts', 'projects']);
        // "return view('users.show', compact('user'));" // make view
    }

    public function projects(User $user)
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('active');
    }

    public function index()
    {
        $users = User::latest()->get();
        // "return view('users.index', compact('users'));" // make view
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'netid' => 'required|string|unique:users,netid|max:255', 
            'email' => 'required|string|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed', 
            'active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'netid' => $validatedData['netid'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']), 
            'active' => $validatedData['active'] ?? true, 
        ]);

        // "return redirect()->route('users.index')->with('message', 'User created successfully!');" // make view
    }
}
