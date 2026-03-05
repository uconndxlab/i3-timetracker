<?php

namespace App\Http\Controllers;

use App\Actions\Projects\AssignUserProject;
use App\Models\Project;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Actions\Shifts\BuildWeeklyChart;

class AdminController extends Controller
{
    public function landing()
    {
        $netid = auth()->user()->netid;

        $activeProjects = Project::where('projects.active', true)
            ->assignedToUser($netid)
            ->latest('updated_at')
            ->get();
        
        foreach ($activeProjects as $project) {
            $hours = $project->getHoursForUser($netid);
            $project->billed_hours = $hours['billed_hours'];
            $project->unbilled_hours = $hours['unbilled_hours'];
        }
        
        $activeShifts = Shift::latest('updated_at')->get()->where('netid', $netid);
        $chartData = app(BuildWeeklyChart::class)($netid);
        $hoursThisWeek = $chartData['hoursThisWeek'];
        $dailyHours = $chartData['dailyHours'];
        $weeklyChartData = $chartData['weeklyChartData'];

        return view('landing', compact('activeProjects', 'activeShifts', 'hoursThisWeek', 'dailyHours', 'weeklyChartData'));
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

    public function markProjectRemainingBilled(Project $project)
    {
        $unbilledShifts = $project->shifts()->where('billed', false)->get();
        $shiftCount = $unbilledShifts->count();
        
        if ($shiftCount > 0) {
            $project->shifts()->where('billed', false)->update(['billed' => true]);
            return redirect()->back()->with('success', "{$shiftCount} shift(s) marked as billed successfully.");
        }
        
        return redirect()->back()->with('info', 'No unbilled shifts to mark.');
    }

    public function batchUpdateShifts(Request $request, Project $project)
    {
        $updates = $request->input('updates', []);
        
        if (empty($updates)) {
            return response()->json(['success' => false, 'message' => 'No updates provided']);
        }
        
        $updatedCount = 0;
        
        foreach ($updates as $shiftId => $changes) {
            $shift = Shift::where('id', $shiftId)
                ->whereHas('project', function($query) use ($project) {
                    $query->where('id', $project->id);
                })
                ->first();
            
            if ($shift) {
                $updateData = [];
                
                if (isset($changes['billed'])) {
                    $updateData['billed'] = (bool) $changes['billed'];
                }
                
                if (isset($changes['entered'])) {
                    $updateData['entered'] = (bool) $changes['entered'];
                }
                
                if (!empty($updateData)) {
                    $shift->update($updateData);
                    $updatedCount++;
                }
            }
        }
        
        return response()->json([
            'success' => true, 
            'message' => "{$updatedCount} shift(s) updated successfully",
            'updated_count' => $updatedCount
        ]);
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
        $result = app(AssignUserProject::class)($project, $validated['user_ids']);

        if ($result['assigned_count'] > 0) {
            return redirect()->route('projects.index', $project->id)
                ->with('success', $result['assigned_count'] . ' user(s) successfully assigned to project.');
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

    
    public function viewAllUsers(Request $request)
    {
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');
        $adminFilter = $request->input('admin_filter');
        $activeFilter = $request->input('active_filter');
        $search = $request->input('search');
        
        $query = User::query()
            ->search($search)
            ->filterAdmin($adminFilter)
            ->filterActive($activeFilter)
            ->withCount('shifts')
            ->withSum('shifts', 'duration');
        
        if ($sortField) {
            $query->orderBy($sortField, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $users = $query->paginate(50)->appends($request->query());
        foreach ($users as $user) {
            $user->total_shifts = $user->shifts_count;
            $user->total_hours = round(($user->shifts_sum_duration ?? 0) / 60, 2);
        }
        
        return view('admin.users', compact('users', 'adminFilter', 'activeFilter', 'search'));
    }

    public function toggleAdmin(User $user)
    {
        if ($user->netid === auth()->user()->netid) {
            return redirect()->back()->with('error', 'You cannot change your own admin status.');
        }
        
        $user->is_admin = !$user->is_admin;
        $user->save();
        
        $status = $user->is_admin ? 'granted' : 'revoked';
        return redirect()->back()->with('message', "Admin privileges {$status} for {$user->name}.");
    }
}
