<?php

namespace App\Http\Controllers;

use App\Actions\Admin\BuildAdminDashboard;
use App\Actions\Projects\AssignUserProject;
use App\Actions\Shifts\BuildAllTimeStatistics;
use App\Actions\Shifts\BuildHoursTimeline;
use App\Actions\Shifts\BuildWeeklyChart;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function landing()
    {
        $authUser = auth()->user();

        return view('landing', $this->landingViewData(
            $authUser,
            $authUser,
            includeAdminDashboard: $authUser->isAdmin(),
            dashboardReadOnly: false,
        ));
    }

    public function viewUserLanding(User $user)
    {
        return view('landing', $this->landingViewData(
            $user,
            auth()->user(),
            includeAdminDashboard: false,
            dashboardReadOnly: true,
        ));
    }

    private function landingViewData(
        User $subjectUser,
        User $authUser,
        bool $includeAdminDashboard,
        bool $dashboardReadOnly,
    ): array {
        $netid = $subjectUser->netid;
        $viewerIsAdmin = $authUser->isAdmin();

        $chartData = app(BuildWeeklyChart::class)($netid, 20, $viewerIsAdmin);
        $weeklyChartData = $chartData['weeklyChartData'];
        $currentWeekIndex = $chartData['currentWeekIndex'];
        $allTimeStats = app(BuildAllTimeStatistics::class)($netid);
        $hoursTimeline = app(BuildHoursTimeline::class)($netid);
        $userProjects = Project::where('projects.active', true)
            ->assignedToUser($netid)
            ->orderBy('name')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ])
            ->values();

        if ($viewerIsAdmin && ! $dashboardReadOnly) {
            $logShiftProjects = Project::where('active', true)->orderBy('name')->get();
        } else {
            $logShiftProjects = Project::where('projects.active', true)
                ->assignedToUser($netid)
                ->orderBy('name')
                ->get();
        }

        $nextShiftNumber = Shift::where('netid', $netid)->count() + 1;
        $defaultShiftDate = now()->setTimezone('America/New_York')->format('Y-m-d');

        $joinedProjectIds = $subjectUser->projects()
            ->where('projects.active', true)
            ->pluck('projects.id')
            ->all();

        $joinableProjects = Project::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'joined' => in_array($project->id, $joinedProjectIds, true),
            ])
            ->values();

        $adminDashboard = $includeAdminDashboard && $viewerIsAdmin
            ? app(BuildAdminDashboard::class)()
            : null;

        return compact(
            'weeklyChartData',
            'currentWeekIndex',
            'allTimeStats',
            'hoursTimeline',
            'userProjects',
            'logShiftProjects',
            'nextShiftNumber',
            'defaultShiftDate',
            'joinableProjects',
            'adminDashboard',
            'dashboardReadOnly',
            'subjectUser',
        );
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $logoutUrl = cas()->logout(url('/'));

        return redirect()->away($logoutUrl);
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
                ->whereHas('project', function ($query) use ($project) {
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

                if (! empty($updateData)) {
                    $shift->update($updateData);
                    $updatedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} shift(s) updated successfully",
            'updated_count' => $updatedCount,
        ]);
    }

    public function assignUsers(Request $request, Project $project)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,netid',
        ]);
        $result = app(AssignUserProject::class)($project, $validated['user_ids']);

        if ($result['assigned_count'] > 0) {
            return redirect()->route('landing', ['view' => 'admin'])
                ->with('success', $result['assigned_count'].' user(s) successfully assigned to project.');
        }

        return redirect()->route('landing', ['view' => 'admin'])
            ->with('info', 'All selected users were already assigned to this project.');
    }

    public function removeUser(Project $project, $netid)
    {
        $project->users()->detach($netid);

        return redirect()->route('landing', ['view' => 'admin'])
            ->with('success', 'User successfully removed from project.');
    }

    public function toggleAdmin(User $user)
    {
        if ($user->netid === auth()->user()->netid) {
            return redirect()->back()->with('error', 'You cannot change your own admin status.');
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        $status = $user->is_admin ? 'granted' : 'revoked';

        return redirect()->route('landing', ['view' => 'admin'])
            ->with('message', "Admin privileges {$status} for {$user->name}.");
    }
}
