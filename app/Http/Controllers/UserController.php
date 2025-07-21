<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'netid' => $validatedData['netid'],
            'email' => $validatedData['email'],
            'active' => $validatedData['active'] ?? true, 
        ]);

        return redirect()->route('landing')->with('message', 'User registered successfully!');
    }
}
