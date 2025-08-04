<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function landing()
    {
        $activeProjects = Project::where('active', true)->latest('updated_at')->get()->filter(function($project) {
            return $project->users->contains('netid', auth()->user()->netid);
        });
        $activeShifts = Shift::latest('updated_at')->get()->where('netid', auth()->user()->netid);
        return view('landing', compact('activeProjects', 'activeShifts'));
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
        $usersWithUnbilledShifts = User::whereHas('shifts', function($query) use ($project) {
            $query->where('proj_id', $project->id)
                ->where('billed', false);
        })->with(['shifts' => function($query) use ($project) {
            $query->where('proj_id', $project->id)
                ->where('billed', false)
                ->with('project');
        }])->get();

        return view('admin.project_unbilled_users', compact('project', 'usersWithUnbilledShifts'));
    }


    
    public function markShiftBilled(Shift $shift)
    {
        $shift->update(['billed' => true]);
        return redirect()->back()->with('success', 'Shift marked as billed successfully');
    }



    public function showProjectUsers(Project $project)
    {
        $project = Project::findOrFail($project->id);
        
        $assignedUsers = User::join('project_user', 'users.netid', '=', 'project_user.user_netid')
            ->where('project_user.project_id', $project->id)
            ->select('users.*', 'project_user.active as is_active')
            ->get();
        
            //using active column to help with UI state
        $assignedUsers->transform(function ($user) {
            $user->pivot = (object)['active' => $user->is_active];
            return $user;
        });
        
        $assignedNetids = $assignedUsers->pluck('netid')->toArray();
        $unassignedUsers = User::whereNotIn('netid', $assignedNetids)->get();
        
        return view('admin.project_users', compact('project', 'assignedUsers', 'unassignedUsers'));
    }


    /**
     * Assign users to a project.
     */
    public function assignUsers(Request $request, Project $project)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,netid',
        ]);
        $existingUserNetids = $project->users->pluck('netid')->toArray();
        $newUserNetids = array_diff($validated['user_ids'], $existingUserNetids);
        
        if (!empty($newUserNetids)) {
            $syncData = [];
            foreach ($newUserNetids as $netid) {
                $syncData[$netid] = ['active' => true];
            }
            $project->users()->syncWithoutDetaching($syncData);
            
            return redirect()->route('admin.projects.users', $project->id)
                ->with('success', count($newUserNetids) . ' user(s) successfully assigned to project.');
        }
        
        return redirect()->route('admin.projects.users', $project->id)
            ->with('info', 'All selected users were already assigned to this project.');
    }


    /**
     * Remove a user from a project.
     */
    public function removeUser(Project $project, $netid)
    {
        $project->users()->detach($netid);
        
        return redirect()->route('admin.projects.users', $project->id)
            ->with('success', 'User successfully removed from project.');
    }
}