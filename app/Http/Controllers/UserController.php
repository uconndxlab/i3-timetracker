<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShiftController;
use App\Models\Project_User;

class UserController extends Controller
{
    public function create()
    {
        if (cas()->isAuthenticated()) {
            $netid = cas()->user();

            if (User::where('netid', $netid)->exists()) {
                return redirect()->route('landing')->with('message', 'Your account already exists.');
            }

            return view('users.create', ['netid' => $netid]);
        }
        
        return cas()->authenticate();
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'netid' => 'sometimes|required|string|max:255|unique:users,netid,' . $user->netid,
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'active' => 'sometimes|boolean',
            'is_admin' => 'sometimes|boolean',
        ]);
        
        $user->update($validatedData);
        // "return redirect()->route('users.show', $user)->with('message', 'User updated successfully!');" // make view
    }

    
    public function show(User $user)
    {
    //     $projects = Project_User::where('user_id', $user->id)->with('project')->get();
    //     $shifts = $user->shifts()->with('project')->get();
    //     return view('users.show', compact('user', 'projects', 'shifts'));
        $user->load(['projects', 'shifts.project']);
        return view('users.show', compact('user'));
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
            'active' => 'sometimes|boolean',
            'is_admin' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'netid' => $validatedData['netid'],
            'email' => $validatedData['email'],
            'active' => $validatedData['active'] ?? true,
            'is_admin' => $validatedData['is_admin'] ?? false,
        ]);

        return redirect()->route('landing')->with('message', 'User registered successfully!');
    }
}
