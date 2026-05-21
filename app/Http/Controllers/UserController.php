<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function create()
    {
        $netid = cas()->user();

        if (User::where('netid', $netid)->exists()) {
            return redirect()->route('landing')->with('message', 'Your account already exists.');
        }

        return view('users.create', ['netid' => $netid]);
    }

    public function store(Request $request)
    {
        if (! cas()->isAuthenticated()) {
            cas()->authenticate();
        }

        $netid = cas()->user();

        if (User::where('netid', $netid)->exists()) {
            return redirect()->route('landing')->with('message', 'Your account already exists.');
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $email = $netid.'@uconn.edu';
        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['netid' => 'A user with this NetID already has a registered email.'])->withInput();
        }

        User::create([
            'name' => $validatedData['name'],
            'netid' => $netid,
            'email' => $email,
            'active' => true,
            'is_admin' => false,
        ]);

        return redirect()->route('landing')->with('message', 'User registered successfully!');
    }
}
