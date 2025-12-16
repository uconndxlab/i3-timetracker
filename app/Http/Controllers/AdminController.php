<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Mail\UserAddedToProject;
use Illuminate\Support\Facades\Mail;

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
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->get();

        $totalMinutesThisWeek = $shiftsThisWeek->reduce(function ($carry, $shift) {
            return $carry + ($shift->duration ?? 0);
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

    public function viewAllShifts(Request $request)
    {
        $user = auth()->user();
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');
        $enteredFilter = $request->input('entered_filter');
        $billedFilter = $request->input('billed_filter');
        $search = $request->input('search'); // Get search parameter
        
        $query = Shift::query();
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }
        
        if ($enteredFilter !== null && $enteredFilter !== '') {
            $query->where('entered', $enteredFilter == '1');
        }
        
        if ($billedFilter !== null && $billedFilter !== '') {
            $query->where('billed', $billedFilter == '1');
        }
        
        if ($sortField === 'project.name') {
            $query->join('projects', 'shifts.proj_id', '=', 'projects.id')
                ->select('shifts.*')
                ->orderBy('projects.name', $direction);
        }
        else if ($sortField === 'user.name') {
            $query->leftJoin('users', 'shifts.netid', '=', 'users.netid')
                ->select('shifts.*')
                ->orderByRaw('CASE WHEN users.name IS NULL THEN 1 ELSE 0 END, users.name ' . $direction);
        }
        else if ($sortField === 'shift_date') {
            $query->orderBy('date', $direction);
        }
        else if ($sortField === 'duration') {
            $query->orderBy('duration', $direction);
        }
        else if ($sortField === 'entered' || $sortField === 'billed') {
            $query->orderBy($sortField, $direction);
        }
        else if ($sortField) {
            $query->orderBy($sortField, $direction);
        }
        else {
            $query->orderBy('date', 'desc');
        }
        
        $shifts = $query->with(['user', 'project'])->paginate(50)->appends($request->query());
        
        foreach ($shifts as $shift) {
            $shift->shift_date = $shift->date ? $shift->date->format('M d, Y') : '-';
            $shift->can_edit = $user->isAdmin() || 
                ($shift->netid === $user->netid && !$shift->entered && !$shift->billed);
        }
        
        return view('admin.shifts', compact('shifts', 'enteredFilter', 'billedFilter', 'search'));
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

            foreach ($newUserNetids as $netid) {
                $user = User::where('netid', $netid)->first();
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new UserAddedToProject($user, $project));
                }
            }
            
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

    
    public function viewAllUsers(Request $request)
    {
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');
        $adminFilter = $request->input('admin_filter');
        $activeFilter = $request->input('active_filter');
        $search = $request->input('search');
        
        $query = User::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('netid', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        if ($adminFilter !== null && $adminFilter !== '') {
            $query->where('is_admin', $adminFilter == '1');
        }
        
        if ($activeFilter !== null && $activeFilter !== '') {
            $query->where('active', $activeFilter == '1');
        }
        
        if ($sortField) {
            $query->orderBy($sortField, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $users = $query->paginate(50)->appends($request->query());
        foreach ($users as $user) {
            $user->total_shifts = $user->shifts()->count();
            $user->total_hours = round($user->shifts()->get()->reduce(function ($carry, $shift) {
                return $carry + ($shift->duration ? $shift->duration / 60 : 0);
            }, 0), 2);
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
