<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\Shifts\BuildHoursTimeline;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use App\Services\HoneycrispService;
use App\Support\PayPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildAdminDashboard
{
    public function __invoke(?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($dateFrom, $dateTo);
        $hasDateFilter = $dateFrom !== null && $dateTo !== null;

        $activeRange = $this->buildRangeDetail($dateFrom, $dateTo, $hasDateFilter);

        $activeProjects = Project::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $honeycrisp = app(HoneycrispService::class);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'has_date_filter' => $hasDateFilter,
            'active_range' => $activeRange,
            'org_projects' => $this->buildOrgProjects(
                $activeProjects->pluck('name', 'id'),
                $dateFrom,
                $dateTo,
            ),
            'org_stats' => $this->buildOrgStats($dateFrom, $dateTo, $hasDateFilter),
            'hours_timeline' => app(BuildHoursTimeline::class)(null),
            'products' => $honeycrisp->products(),
            'honeycrisp_projects' => $honeycrisp->projects(),
            'projects' => $activeProjects
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                ])
                ->values()
                ->all(),
        ];
    }

    private function buildRangeDetail(?string $startDate, ?string $endDate, bool $hasDateFilter): array
    {
        $shifts = $this->loadShifts($startDate, $endDate);

        $userNames = User::query()->pluck('name', 'netid');
        $productIds = User::query()->pluck('honeycrisp_product_id', 'netid');
        $honeycrispProjectIds = Project::query()->pluck('honeycrisp_project_id', 'id');

        if ($hasDateFilter) {
            $employees = $this->buildEmployeeRowsForRange($shifts, $userNames, $productIds);
            $projectRows = $this->buildProjectRowsForRange($shifts, $honeycrispProjectIds);
            $days = $this->buildDailySeriesForRange($shifts, $startDate, $endDate);
        } else {
            $allTimeByUser = $this->loadAllTimeByUser();
            $topProjectByUser = $this->loadTopProjectByNetid();
            $allTimeByProject = $this->loadAllTimeByProject();
            $topEmployeeByProject = $this->loadTopEmployeeByProject();
            $projectNames = Project::query()
                ->where('active', true)
                ->orderBy('name')
                ->pluck('name', 'id');

            $employees = $this->buildEmployeeRowsAllTime(
                $allTimeByUser,
                $topProjectByUser,
                $projectNames,
                $userNames,
                $productIds,
            );
            $projectRows = $this->buildProjectRowsAllTime(
                $allTimeByProject,
                $topEmployeeByProject,
                $projectNames,
                $honeycrispProjectIds,
            );
            $days = [];
        }

        return [
            'hours_in_range' => $this->sumHours($shifts),
            'employees' => $employees,
            'project_rows' => $projectRows,
            'shifts' => $this->buildShiftRows($shifts),
            'days' => $days,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $this->parseDate($dateFrom);
        $to = $this->parseDate($dateTo);

        if ($from === null || $to === null) {
            return [null, null];
        }

        if ($from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    private function parseDate(?string $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function buildShiftRows(Collection $shifts): array
    {
        return $shifts
            ->sortByDesc(fn ($shift) => $shift->date.$shift->id)
            ->values()
            ->map(fn ($shift) => Shift::formatAdminRow($shift))
            ->all();
    }

    private function loadShifts(?string $rangeStart, ?string $rangeEnd): Collection
    {
        $query = DB::table('shifts')
            ->join('users', 'shifts.netid', '=', 'users.netid')
            ->join('projects', 'shifts.proj_id', '=', 'projects.id')
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
            ->orderByDesc('shifts.id');

        if ($rangeStart !== null) {
            $query->whereDate('shifts.date', '>=', $rangeStart);
        }

        if ($rangeEnd !== null) {
            $query->whereDate('shifts.date', '<=', $rangeEnd);
        }

        return $query->get();
    }

    /**
     * @param  Collection<string, object>  $allTimeByUser
     * @param  Collection<string, object>  $topProjectByUser
     * @param  Collection<int|string, string>  $projectNames
     */
    private function buildEmployeeRowsAllTime(
        Collection $allTimeByUser,
        Collection $topProjectByUser,
        Collection $projectNames,
        Collection $userNames,
        Collection $productIds,
    ): array {
        return $allTimeByUser
            ->map(function (object $allTime, string $netid) use ($topProjectByUser, $projectNames, $userNames, $productIds) {
                $topProject = $topProjectByUser->get($netid);
                $unbilledHours = round(((int) ($allTime->unbilled_minutes ?? 0)) / 60, 2);
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
                    'last_shift_date_sort' => $allTime->last_date ?? '',
                    'honeycrisp_product_id' => $productIds->get($netid),
                ];
            })
            ->filter(fn (array $row) => $row['unbilled_hours'] > 0 || $row['total_hours'] > 0)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function buildEmployeeRowsForRange(Collection $shifts, Collection $userNames, Collection $productIds): array
    {
        $projectNames = Project::query()->pluck('name', 'id');

        return $shifts
            ->groupBy('netid')
            ->map(function (Collection $employeeShifts, string $netid) use ($userNames, $projectNames, $productIds) {
                $totalMinutes = $employeeShifts->sum(fn ($shift) => $shift->duration ?? 0);
                $unbilledMinutes = $employeeShifts
                    ->filter(fn ($shift) => ! $shift->billed)
                    ->sum(fn ($shift) => $shift->duration ?? 0);

                $topProjectId = $employeeShifts
                    ->groupBy('proj_id')
                    ->map(fn (Collection $projectShifts) => $projectShifts->sum(fn ($shift) => $shift->duration ?? 0))
                    ->sortDesc()
                    ->keys()
                    ->first();

                $lastDate = $employeeShifts->max('date');

                return [
                    'netid' => $netid,
                    'name' => $userNames->get($netid, $netid),
                    'unbilled_hours' => round($unbilledMinutes / 60, 2),
                    'total_hours' => round($totalMinutes / 60, 2),
                    'top_project' => $projectNames->get($topProjectId, '—'),
                    'last_shift_date' => $lastDate
                        ? Carbon::parse($lastDate)->format('n/j/y')
                        : null,
                    'last_shift_date_sort' => $lastDate
                        ? Carbon::parse($lastDate)->format('Y-m-d')
                        : '',
                    'honeycrisp_product_id' => $productIds->get($netid),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int|string, object>  $allTimeByProject
     * @param  Collection<int|string, object>  $topEmployeeByProject
     * @param  Collection<int|string, string>  $projectNames
     * @param  Collection<int|string, string|null>  $honeycrispProjectIds
     */
    private function buildProjectRowsAllTime(
        Collection $allTimeByProject,
        Collection $topEmployeeByProject,
        Collection $projectNames,
        Collection $honeycrispProjectIds,
    ): array {
        return $projectNames
            ->map(function (string $name, $projectId) use ($allTimeByProject, $topEmployeeByProject, $honeycrispProjectIds) {
                $allTime = $allTimeByProject->get($projectId);
                $topEmployee = $topEmployeeByProject->get($projectId);
                $unbilledHours = round(((int) ($allTime->unbilled_minutes ?? 0)) / 60, 2);
                $totalHours = round(((int) ($allTime->total_minutes ?? 0)) / 60, 2);

                return [
                    'id' => $projectId,
                    'name' => $name,
                    'unbilled_hours' => $unbilledHours,
                    'total_hours' => $totalHours,
                    'top_employee' => $topEmployee->employee_name ?? '—',
                    'last_shift_date' => isset($allTime->last_date)
                        ? Carbon::parse($allTime->last_date)->format('n/j/y')
                        : null,
                    'last_shift_date_sort' => $allTime->last_date ?? '',
                    'honeycrisp_project_id' => $honeycrispProjectIds->get($projectId),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int|string, string|null>  $honeycrispProjectIds
     */
    private function buildProjectRowsForRange(Collection $shifts, Collection $honeycrispProjectIds): array
    {
        $projectNames = Project::query()
            ->where('active', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        $shiftsByProject = $shifts->groupBy('proj_id');

        return $projectNames
            ->map(function (string $name, $projectId) use ($shiftsByProject, $honeycrispProjectIds) {
                $projectShifts = $shiftsByProject->get($projectId, collect());
                $honeycrispProjectId = $honeycrispProjectIds->get($projectId);

                if ($projectShifts->isEmpty()) {
                    return [
                        'id' => $projectId,
                        'name' => $name,
                        'unbilled_hours' => 0.0,
                        'total_hours' => 0.0,
                        'top_employee' => '—',
                        'last_shift_date' => null,
                        'last_shift_date_sort' => '',
                        'honeycrisp_project_id' => $honeycrispProjectId,
                    ];
                }

                $totalMinutes = $projectShifts->sum(fn ($shift) => $shift->duration ?? 0);
                $unbilledMinutes = $projectShifts
                    ->filter(fn ($shift) => ! $shift->billed)
                    ->sum(fn ($shift) => $shift->duration ?? 0);

                $topEmployee = $projectShifts
                    ->groupBy('netid')
                    ->map(fn (Collection $employeeShifts) => [
                        'name' => $employeeShifts->first()->employee_name ?? '—',
                        'minutes' => $employeeShifts->sum(fn ($shift) => $shift->duration ?? 0),
                    ])
                    ->sortByDesc('minutes')
                    ->first();

                $lastDate = $projectShifts->max('date');

                return [
                    'id' => $projectId,
                    'name' => $name,
                    'unbilled_hours' => round($unbilledMinutes / 60, 2),
                    'total_hours' => round($totalMinutes / 60, 2),
                    'top_employee' => $topEmployee['name'] ?? '—',
                    'last_shift_date' => $lastDate
                        ? Carbon::parse($lastDate)->format('n/j/y')
                        : null,
                    'last_shift_date_sort' => $lastDate
                        ? Carbon::parse($lastDate)->format('Y-m-d')
                        : '',
                    'honeycrisp_project_id' => $honeycrispProjectId,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, date: string, hours: float}>
     */
    private function buildDailySeriesForRange(Collection $shifts, string $startDate, string $endDate): array
    {
        $minutesByDate = $shifts
            ->groupBy(fn ($shift) => Carbon::parse($shift->date)->format('Y-m-d'))
            ->map(fn (Collection $dayShifts) => $dayShifts->sum(fn ($shift) => $shift->duration ?? 0));

        $days = [];
        $cursor = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        while ($cursor->lte($end)) {
            $dateString = $cursor->format('Y-m-d');
            $days[] = [
                'label' => $cursor->format('n/j'),
                'date' => $dateString,
                'hours' => round(((int) ($minutesByDate[$dateString] ?? 0)) / 60, 2),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  Collection<int|string, string>  $projectNames
     */
    private function buildOrgProjects(Collection $projectNames, ?string $rangeStart, ?string $rangeEnd): array
    {
        $query = DB::table('shifts')
            ->select('proj_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 1 THEN duration ELSE 0 END), 0) / 60 as billed_hours')
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 0 THEN duration ELSE 0 END), 0) / 60 as unbilled_hours')
            ->groupBy('proj_id');

        if ($rangeStart !== null) {
            $query->whereDate('date', '>=', $rangeStart);
        }

        if ($rangeEnd !== null) {
            $query->whereDate('date', '<=', $rangeEnd);
        }

        $hoursByProject = $query->get()->keyBy('proj_id');

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

    private function buildOrgStats(?string $rangeStart, ?string $rangeEnd, bool $hasDateFilter): array
    {
        $query = DB::table('shifts');

        if ($rangeStart !== null) {
            $query->whereDate('date', '>=', $rangeStart);
        }

        if ($rangeEnd !== null) {
            $query->whereDate('date', '<=', $rangeEnd);
        }

        $totalMinutes = (int) (clone $query)->sum('duration');
        $totalHours = round($totalMinutes / 60, 2);

        if ($hasDateFilter) {
            $weeksWorked = PayPeriod::countWeeksWorked(
                (clone $query)->distinct()->pluck('date')
            );
        } else {
            $weeksWorked = PayPeriod::countWeeksWorked(
                DB::table('shifts')->distinct()->pluck('date')
            );
        }

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
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 0 THEN duration ELSE 0 END), 0) as unbilled_minutes')
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
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 0 THEN duration ELSE 0 END), 0) as unbilled_minutes')
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
