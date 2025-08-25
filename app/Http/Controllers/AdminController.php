<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function landing()
    {
        $activeProjects = Project::where('active', true)->latest('updated_at')->get()->filter(function($project) {
            return $project->users->contains('netid', auth()->user()->netid);
        });
        $activeShifts = Shift::latest('updated_at')->get()->where('netid', auth()->user()->netid);
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        $shiftsThisWeek = Shift::where('netid', auth()->user()->netid)
            ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
            ->get();

        $totalMinutesThisWeek = $shiftsThisWeek->reduce(function ($carry, $shift) {
            return $carry + $shift->start_time->diffInMinutes($shift->end_time);
        }, 0);

        $hoursThisWeek = round($totalMinutesThisWeek / 60, 2);
        // small issue of shifts that go from Saturday night -> Sunday morning are between weeks (just gonna count for previous week)

        return view('landing', compact('activeProjects', 'activeShifts', 'hoursThisWeek'));
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



    // public function showProjectUnbilledUsers(Project $project)
    // {
    //     $usersWithUnbilledShifts = User::whereHas('shifts', function($query) use ($project) {
    //         $query->where('proj_id', $project->id)
    //             ->where('billed', false);
    //     })->with(['shifts' => function($query) use ($project) {
    //         $query->where('proj_id', $project->id)
    //             ->where('billed', false)
    //             ->with('project');
    //     }])->get();

    //     return view('admin.project_unbilled_users', compact('project', 'usersWithUnbilledShifts'));
    // }


    
    public function markShiftBilled(Shift $shift)
    {
        $shift->update(['billed' => true]);
        return redirect()->back()->with('success', 'Shift marked as billed successfully');
    }



    // public function showProjectUsers(Project $project)
    // {
    //     $project = Project::findOrFail($project->id);
        
    //     $assignedUsers = User::join('project_user', 'users.netid', '=', 'project_user.user_netid')
    //         ->where('project_user.project_id', $project->id)
    //         ->select('users.*', 'project_user.active as is_active')
    //         ->get();
        
    //         //using active column to help with UI state
    //     $assignedUsers->transform(function ($user) {
    //         $user->pivot = (object)['active' => $user->is_active];
    //         return $user;
    //     });
        
    //     $assignedNetids = $assignedUsers->pluck('netid')->toArray();
    //     $unassignedUsers = User::whereNotIn('netid', $assignedNetids)->get();
        
    //     return view('admin.project_users', compact('project', 'assignedUsers', 'unassignedUsers'));
    // }


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
            
            return redirect()->route('projects.index', $project->id)
                ->with('success', count($newUserNetids) . ' user(s) successfully assigned to project.');
        }

        return redirect()->route('projects.index', $project->id)
            ->with('info', 'All selected users were already assigned to this project.');
    }


    /**
     * Remove a user from a project.
     */
    public function removeUser(Project $project, $netid)
    {
        $project->users()->detach($netid);

        return redirect()->route('projects.index', $project->id)
            ->with('success', 'User successfully removed from project.');
    }

    public function manageProject(Project $project)
    {
        $users = User::orderBy('name')->get();
        $assignedUsers = $project->users;
        
        return view('admin.manage', compact('project', 'users', 'assignedUsers'));
    }
}