<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\Shifts\BuildHoursTimeline;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use App\Support\PayPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildAdminDashboard
{
    public function __invoke(int $weekCount = 20, ?string $periodStart = null): array
    {
        $weeklyPeriods = $this->buildWeeklyPeriodMetadata($weekCount);
        $activeWeekIndex = PayPeriod::resolveActiveIndex($weeklyPeriods, $periodStart);

        $activePeriod = $weeklyPeriods[$activeWeekIndex];
        $periodDetail = $this->buildPeriodDetail($activePeriod['start_date'], $activePeriod['end_date']);
        $activePeriod = array_merge($activePeriod, $periodDetail);

        $weeklyPeriods[$activeWeekIndex] = $activePeriod;

        $activeProjects = Project::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'weekly_periods' => $this->stripPeriodMetadata($weeklyPeriods),
            'active_period' => $activePeriod,
            'current_week_index' => $activeWeekIndex,
            'org_projects' => $this->buildOrgProjects($activeProjects->pluck('name', 'id')),
            'org_stats' => $this->buildOrgStats(),
            'hours_timeline' => app(BuildHoursTimeline::class)(null),
            'projects' => $activeProjects
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                ])
                ->values()
                ->all(),
        ];
    }

    public function buildPeriodDetail(string $startDate, string $endDate): array
    {
        $weekStart = Carbon::parse($startDate)->startOfWeek(PayPeriod::WEEK_START);
        $periodShifts = $this->loadPeriodShifts($startDate, $endDate);

        $allTimeByUser = $this->loadAllTimeByUser();
        $topProjectByUser = $this->loadTopProjectByNetid();
        $allTimeByProject = $this->loadAllTimeByProject();
        $topEmployeeByProject = $this->loadTopEmployeeByProject();
        $projectNames = Project::query()->pluck('name', 'id');
        $userNames = User::query()->pluck('name', 'netid');

        return [
            'hours_this_period' => $this->sumHours($periodShifts),
            'employees' => $this->buildEmployeeRows(
                $periodShifts,
                $allTimeByUser,
                $topProjectByUser,
                $projectNames,
                $userNames,
            ),
            'project_rows' => $this->buildProjectRows(
                $periodShifts,
                $allTimeByProject,
                $topEmployeeByProject,
                $projectNames,
            ),
            'shifts' => $this->buildShiftRows($periodShifts),
            'days' => $this->buildDailySeries($periodShifts, $weekStart),
        ];
    }

    /**
     * @return list<array{label: string, start_date: string, end_date: string, is_current_week: bool, hours_this_period: float, days: list<array>}>
     */
    public function buildWeeklyPeriodMetadata(int $weekCount = 20): array
    {
        $weeks = PayPeriod::buildWeeks($weekCount);
        $firstWeekStart = $weeks[0]['start'];
        $rangeStart = $firstWeekStart->format('Y-m-d');
        $rangeEnd = PayPeriod::currentWeekEnd()->format('Y-m-d');

        $minutesByDate = DB::table('shifts')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->selectRaw('date, SUM(duration) as minutes')
            ->groupBy('date')
            ->pluck('minutes', 'date');

        $periods = [];

        foreach ($weeks as $week) {
            $periods[] = [
                'label' => $week['label'],
                'start_date' => $week['start_date'],
                'end_date' => $week['end_date'],
                'is_current_week' => $week['is_current_week'],
                'hours_this_period' => PayPeriod::sumHoursFromDailyMinutes($minutesByDate, $week['start'], $week['end']),
                'days' => PayPeriod::buildDailySeries($minutesByDate, $week['start']),
            ];
        }

        return $periods;
    }

    /**
     * @param  list<array<string, mixed>>  $weeklyPeriods
     * @return list<array<string, mixed>>
     */
    private function stripPeriodMetadata(array $weeklyPeriods): array
    {
        return array_map(fn (array $period) => [
            'label' => $period['label'],
            'start_date' => $period['start_date'],
            'end_date' => $period['end_date'],
            'is_current_week' => $period['is_current_week'],
            'hours_this_period' => $period['hours_this_period'],
        ], $weeklyPeriods);
    }

    private function buildShiftRows(Collection $periodShifts): array
    {
        return $periodShifts
            ->sortByDesc(fn ($shift) => $shift->date.$shift->id)
            ->values()
            ->map(fn ($shift) => Shift::formatAdminRow($shift))
            ->all();
    }

    private function loadPeriodShifts(string $rangeStart, string $rangeEnd): Collection
    {
        return DB::table('shifts')
            ->join('users', 'shifts.netid', '=', 'users.netid')
            ->join('projects', 'shifts.proj_id', '=', 'projects.id')
            ->whereBetween('shifts.date', [$rangeStart, $rangeEnd])
            ->select([
                'shifts.id',
                'shifts.netid',
                'shifts.proj_id',
                'shifts.date',
                'shifts.duration',
                'shifts.entered',
                'shifts.billed',
                'users.name as employee_name',
                'projects.name as project_name',
            ])
            ->orderByDesc('shifts.date')
            ->orderByDesc('shifts.id')
            ->get();
    }

    /**
     * @param  Collection<string, object>  $allTimeByUser
     * @param  Collection<string, object>  $topProjectByUser
     * @param  Collection<int|string, string>  $projectNames
     */
    private function buildEmployeeRows(
        Collection $periodShifts,
        Collection $allTimeByUser,
        Collection $topProjectByUser,
        Collection $projectNames,
        Collection $userNames,
    ): array {
        $periodUnbilledMinutes = $periodShifts
            ->where('billed', false)
            ->groupBy('netid')
            ->map(fn (Collection $shifts) => $shifts->sum(fn ($shift) => $shift->duration ?? 0));

        $netids = $periodShifts->pluck('netid')
            ->merge($allTimeByUser->keys())
            ->unique();

        return $netids
            ->map(function (string $netid) use ($periodUnbilledMinutes, $allTimeByUser, $topProjectByUser, $projectNames, $userNames) {
                $allTime = $allTimeByUser->get($netid);
                $topProject = $topProjectByUser->get($netid);

                $unbilledHours = round(((int) ($periodUnbilledMinutes[$netid] ?? 0)) / 60, 2);
                $totalHours = round(((int) ($allTime->total_minutes ?? 0)) / 60, 2);

                return [
                    'netid' => $netid,
                    'name' => $userNames->get($netid, $netid),
                    'unbilled_hours' => $unbilledHours,
                    'total_hours' => $totalHours,
                    'top_project' => $projectNames->get($topProject->proj_id ?? null, '—'),
                    'last_shift_date' => isset($allTime->last_date)
                        ? Carbon::parse($allTime->last_date)->format('n/j/y')
                        : null,
                ];
            })
            ->filter(fn (array $row) => $row['unbilled_hours'] > 0 || $row['total_hours'] > 0)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int|string, object>  $allTimeByProject
     * @param  Collection<int|string, object>  $topEmployeeByProject
     * @param  Collection<int|string, string>  $projectNames
     */
    private function buildProjectRows(
        Collection $periodShifts,
        Collection $allTimeByProject,
        Collection $topEmployeeByProject,
        Collection $projectNames,
    ): array {
        $periodMinutes = $periodShifts
            ->groupBy('proj_id')
            ->map(fn (Collection $shifts) => $shifts->sum(fn ($shift) => $shift->duration ?? 0));

        $projectIds = $periodShifts->pluck('proj_id')
            ->merge($allTimeByProject->keys())
            ->unique();

        return $projectIds
            ->map(function ($projectId) use ($periodMinutes, $allTimeByProject, $topEmployeeByProject, $projectNames) {
                $allTime = $allTimeByProject->get($projectId);
                $topEmployee = $topEmployeeByProject->get($projectId);

                $hoursLastPeriod = round(((int) ($periodMinutes[$projectId] ?? 0)) / 60, 2);
                $totalHours = round(((int) ($allTime->total_minutes ?? 0)) / 60, 2);

                return [
                    'id' => $projectId,
                    'name' => $projectNames->get($projectId, 'Unknown project'),
                    'hours_last_period' => $hoursLastPeriod,
                    'total_hours' => $totalHours,
                    'top_employee' => $topEmployee->employee_name ?? '—',
                    'last_shift_date' => isset($allTime->last_date)
                        ? Carbon::parse($allTime->last_date)->format('n/j/y')
                        : null,
                ];
            })
            ->filter(fn (array $row) => $row['hours_last_period'] > 0 || $row['total_hours'] > 0)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function buildDailySeries(Collection $periodShifts, Carbon $weekStart): array
    {
        $minutesByDate = $periodShifts
            ->groupBy(fn ($shift) => Carbon::parse($shift->date)->format('Y-m-d'))
            ->map(fn (Collection $dayShifts) => $dayShifts->sum(fn ($shift) => $shift->duration ?? 0));

        return PayPeriod::buildDailySeries($minutesByDate, $weekStart);
    }

    /**
     * @param  Collection<int|string, string>  $projectNames
     */
    private function buildOrgProjects(Collection $projectNames): array
    {
        $hoursByProject = DB::table('shifts')
            ->select('proj_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 1 THEN duration ELSE 0 END), 0) / 60 as billed_hours')
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 0 THEN duration ELSE 0 END), 0) / 60 as unbilled_hours')
            ->groupBy('proj_id')
            ->get()
            ->keyBy('proj_id');

        return $projectNames
            ->map(function (string $name, $projectId) use ($hoursByProject) {
                $hours = $hoursByProject->get($projectId);

                return [
                    'id' => $projectId,
                    'name' => $name,
                    'billed_hours' => round((float) ($hours->billed_hours ?? 0), 2),
                    'unbilled_hours' => round((float) ($hours->unbilled_hours ?? 0), 2),
                ];
            })
            ->filter(fn (array $row) => $row['billed_hours'] > 0 || $row['unbilled_hours'] > 0)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function buildOrgStats(): array
    {
        $totalMinutes = (int) DB::table('shifts')->sum('duration');
        $totalHours = round($totalMinutes / 60, 2);

        $weeksWorked = PayPeriod::countWeeksWorked(
            DB::table('shifts')->distinct()->pluck('date')
        );

        $avgHoursPerWeek = $weeksWorked > 0
            ? round($totalHours / $weeksWorked, 2)
            : 0.0;

        return [
            'total_hours' => $totalHours,
            'avg_hours_per_week' => $avgHoursPerWeek,
        ];
    }

    private function loadAllTimeByUser(): Collection
    {
        return DB::table('shifts')
            ->select('netid')
            ->selectRaw('COALESCE(SUM(duration), 0) as total_minutes')
            ->selectRaw('MAX(date) as last_date')
            ->groupBy('netid')
            ->get()
            ->keyBy('netid');
    }

    private function loadTopProjectByNetid(): Collection
    {
        return DB::table('shifts')
            ->select('netid', 'proj_id')
            ->selectRaw('SUM(duration) as total_minutes')
            ->groupBy('netid', 'proj_id')
            ->orderBy('netid')
            ->orderByDesc('total_minutes')
            ->get()
            ->groupBy('netid')
            ->map->first();
    }

    private function loadAllTimeByProject(): Collection
    {
        return DB::table('shifts')
            ->select('proj_id')
            ->selectRaw('COALESCE(SUM(duration), 0) as total_minutes')
            ->selectRaw('MAX(date) as last_date')
            ->groupBy('proj_id')
            ->get()
            ->keyBy('proj_id');
    }

    private function loadTopEmployeeByProject(): Collection
    {
        return DB::table('shifts')
            ->join('users', 'shifts.netid', '=', 'users.netid')
            ->select('shifts.proj_id', 'shifts.netid', 'users.name as employee_name')
            ->selectRaw('SUM(shifts.duration) as total_minutes')
            ->groupBy('shifts.proj_id', 'shifts.netid', 'users.name')
            ->orderBy('shifts.proj_id')
            ->orderByDesc('total_minutes')
            ->get()
            ->groupBy('proj_id')
            ->map->first();
    }

    private function sumHours(Collection $shifts): float
    {
        return round($shifts->sum(fn ($shift) => $shift->duration ?? 0) / 60, 2);
    }
}
