<?php

namespace App\Http\Controllers;

use App\Actions\Admin\BuildAdminDashboard;
use App\Actions\Admin\BuildProjectOverview;
use App\Actions\Shifts\BuildAllTimeStatistics;
use App\Actions\Shifts\BuildAnnualView;
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
        $overview = app(BuildProjectOverview::class)($project);

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

    public function annual()
    {
        $authUser = auth()->user();

        return view('annual', $this->annualViewData(
            $authUser,
            $authUser,
            dashboardReadOnly: false,
        ));
    }

    public function viewUserAnnual(User $user)
    {
        return view('annual', $this->annualViewData(
            $user,
            auth()->user(),
            dashboardReadOnly: true,
        ));
    }

    public function updateAnnualCalView()
    {
        request()->validate(['view' => 'required|in:full,compact']);

        auth()->user()->update(['annual_cal_view' => request('view')]);

        return redirect()->back();
    }

    private function annualViewData(User $subjectUser, User $authUser, bool $dashboardReadOnly): array
    {
        $netid         = $subjectUser->netid;
        $viewerIsAdmin = $authUser->isAdmin();

        $rawDate      = request()->query('date');
        $today        = now()->setTimezone('America/New_York')->format('Y-m-d');
        $selectedDate = $today;

        if ($rawDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
            try {
                $selectedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $rawDate)->format('Y-m-d');
            } catch (\Exception) {
                $selectedDate = $today;
            }
        }

        $year = (int) substr($selectedDate, 0, 4);

        $annualData   = app(BuildAnnualView::class)($netid, $year, $selectedDate, $viewerIsAdmin);
        $calendarData = $this->buildCalendarData($year, $selectedDate, $today);
        $calView      = in_array($subjectUser->annual_cal_view, ['full', 'compact'], true)
            ? $subjectUser->annual_cal_view
            : 'full';

        $isAdminViewer = $viewerIsAdmin && ! $dashboardReadOnly;

        if (! $dashboardReadOnly) {
            if ($isAdminViewer) {
                $logShiftProjects = Project::where('active', true)->orderBy('name')->get();
            } else {
                $logShiftProjects = Project::where('projects.active', true)
                    ->assignedToUser($netid)
                    ->orderBy('name')
                    ->get();
            }

            $nextShiftNumber = Shift::where('netid', $netid)->count() + 1;
        } else {
            $logShiftProjects = collect();
            $nextShiftNumber  = 0;
        }

        $defaultShiftDate = $selectedDate;

        return array_merge(
            compact(
                'subjectUser',
                'dashboardReadOnly',
                'isAdminViewer',
                'selectedDate',
                'year',
                'today',
                'annualData',
                'calView',
                'logShiftProjects',
                'nextShiftNumber',
                'defaultShiftDate',
            ),
            $calendarData,
        );
    }

    private function buildCalendarData(int $year, string $selectedDate, string $today): array
    {
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $dayLabels  = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        $selectedCarbon = \Carbon\Carbon::createFromFormat('Y-m-d', $selectedDate);
        $isToday        = $selectedDate === $today;

        // Year navigation — same month/day in adjacent year, clamped for leap years
        $prevDate = \Carbon\Carbon::createFromDate($year - 1, $selectedCarbon->month, 1)
            ->endOfMonth()
            ->min(\Carbon\Carbon::createFromDate($year - 1, $selectedCarbon->month, $selectedCarbon->day))
            ->format('Y-m-d');

        $nextDate = \Carbon\Carbon::createFromDate($year + 1, $selectedCarbon->month, 1)
            ->endOfMonth()
            ->min(\Carbon\Carbon::createFromDate($year + 1, $selectedCarbon->month, $selectedCarbon->day))
            ->format('Y-m-d');

        // Build compact (GitHub-style) calendar weeks
        $jan1        = \Carbon\Carbon::createFromDate($year, 1, 1);
        $dec31       = \Carbon\Carbon::createFromDate($year, 12, 31);
        $startSunday = $jan1->copy()->subDays($jan1->dayOfWeek);
        $cursor      = $startSunday->copy();

        $compactWeeks       = [];
        $compactMonthLabels = [];

        while ($cursor->lte($dec31)) {
            $week = [];
            for ($dow = 0; $dow < 7; $dow++) {
                if ($cursor->year === $year) {
                    $ds = $cursor->format('Y-m-d');
                    if ($cursor->day === 1) {
                        $compactMonthLabels[] = [
                            'label'     => $monthNames[$cursor->month - 1],
                            'weekIndex' => count($compactWeeks),
                        ];
                    }
                    $week[] = $ds;
                } else {
                    $week[] = null;
                }
                $cursor->addDay();
            }
            $compactWeeks[] = $week;
        }

        $compactTotalWeeks = count($compactWeeks);

        return compact(
            'monthNames',
            'dayLabels',
            'selectedCarbon',
            'isToday',
            'prevDate',
            'nextDate',
            'compactWeeks',
            'compactMonthLabels',
            'compactTotalWeeks',
        );
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
            ? app(BuildAdminDashboard::class)(
                dateFrom: request()->query('date_from'),
                dateTo: request()->query('date_to'),
            )
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
