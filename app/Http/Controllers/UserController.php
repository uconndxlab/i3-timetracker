<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShiftController;
use App\Models\Project_User;
use App\Models\Project;

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
        $user = auth()->user();
        $netid = $user->netid;
        $projects = Project::whereHas('users', function($query) use ($netid) {
            $query->where('user_netid', $netid);
        })->with(['users' => function($query) use ($netid) {
            $query->where('user_netid', $netid);
        }])->get();
        
        $user->load([
            'shifts' => function($query) {
                $query->latest('start_time')->limit(10);
            },
            'shifts.project'
        ]);
        
        return view('users.show', compact('user', 'projects'));
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
        ]);

        $email = $validatedData['netid'] . '@uconn.edu';
        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['netid' => 'A user with this NetID already has a registered email.'])->withInput();
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'netid' => $validatedData['netid'],
            'email' => $email,
            'active' => true,
            'is_admin' => false,
        ]);

        // auto assign to general project for any user
        $generalProject = Project::where('name', 'i3 (General)')->first();
        if ($generalProject) {
            $generalProject->users()->attach($user->netid, ['active' => true]);
        }

        return redirect()->route('landing')->with('message', 'User registered successfully!');
    }
}
