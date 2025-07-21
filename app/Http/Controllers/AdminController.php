<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Display a dashboard for admins, listing all projects.
     */
    public function dashboard()
    {
        $projects = Project::orderBy('name')->get();
        return view('admin.dashboard', compact('projects'));
    }

    /**
     * Show users with unbilled shifts for a specific project.
     */
    public function showProjectUnbilledUsers(Project $project)
    {
        $usersWithUnbilledShifts = User::select('users.*')
        ->join('shifts', 'users.netid', '=', 'shifts.netid') 
        ->where('shifts.proj_id', $project->id)
        ->where('shifts.billed', false)
        ->distinct()
        ->with(['shifts' => function ($query) use ($project) {
            $query->where('proj_id', $project->id)
                  ->where('billed', false)
                  ->orderBy('start_time', 'desc');
        }])
        ->orderBy('users.name')
        ->get();

        return view('admin.project_unbilled_users', compact('project', 'usersWithUnbilledShifts'));
    }

    public function landing()
    {
        $activeProjects = Project::where('active', true)->latest('updated_at')->get();
        return view('landing', compact('activeProjects'));
    }

    public function login()
    {
        return redirect()->route('landing');
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $logoutUrl = cas()->logout(url('/'));
        return redirect()->away($logoutUrl);
        
    }
}