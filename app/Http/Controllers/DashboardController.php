<?php

namespace App\Http\Controllers;

use App\Actions\Admin\BuildAdminDashboard;
use App\Actions\Admin\BuildProjectOverview;
use App\Actions\Shifts\BuildAllTimeStatistics;
use App\Actions\Shifts\BuildHoursTimeline;
use App\Actions\Shifts\BuildWeeklyChart;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;

class DashboardController extends Controller
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

    public function viewProjectOverview(Project $project)
    {
        $overview = app(BuildProjectOverview::class)(
            $project,
            request()->query('period_start'),
        );

        $logShiftProjects = Project::where('active', true)->orderBy('name')->get();
        $nextShiftNumber = Shift::count() + 1;
        $defaultShiftDate = now()->setTimezone('America/New_York')->format('Y-m-d');
        $isAdminViewer = true;
        $adminDashboard = [
            'projects' => $logShiftProjects
                ->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name])
                ->values()
                ->all(),
        ];

        return view('admin.project-overview', compact(
            'overview',
            'logShiftProjects',
            'nextShiftNumber',
            'defaultShiftDate',
            'isAdminViewer',
            'adminDashboard',
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

        $isAdminViewer = $viewerIsAdmin && ! $dashboardReadOnly;
        $loadAdminDashboard = $includeAdminDashboard && $isAdminViewer && request()->query('view') === 'admin';
        $adminDashboard = $loadAdminDashboard
            ? app(BuildAdminDashboard::class)(periodStart: request()->query('period_start'))
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
            'isAdminViewer',
            'dashboardReadOnly',
            'subjectUser',
        );
    }
}
