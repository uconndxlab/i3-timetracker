<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'netid' => 'required|string|unique:users,netid',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'string|min:5|nullable',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'netid' => $validatedData['netid'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password'] ?? null),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'netid' => 'required|string|unique:users,netid,' . $user->id,
            'email' => 'required|string|email|unique:users,email,' . $user->id,
            'password' => 'string|min:5|nullable',
        ]);

        if (isset($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }
        $user->update($validatedData);
    }

    
    public function show(User $user)
    {
        # Get the user details and return to a view.
    }
}
