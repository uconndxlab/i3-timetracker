<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\Projects\ProjectHours;
use App\Actions\Shifts\BuildHoursTimeline;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildAdminDashboard
{
    private const WEEK_START = Carbon::THURSDAY;

    private const WEEK_END = Carbon::WEDNESDAY;

    public function __invoke(int $weekCount = 20): array
    {
        $allShifts = Shift::query()->with(['user', 'project'])->get();

        $startOfWeek = Carbon::now()->startOfWeek(self::WEEK_START);
        $endOfWeek = Carbon::now()->endOfWeek(self::WEEK_END);
        $firstWeekStart = $startOfWeek->copy()->subWeeks($weekCount - 1);

        $weeklyPeriods = [];

        for ($weekOffset = 0; $weekOffset < $weekCount; $weekOffset++) {
            $weekStart = $firstWeekStart->copy()->addWeeks($weekOffset);
            $weekEnd = $weekStart->copy()->endOfWeek(self::WEEK_END);
            $periodShifts = $this->filterShiftsBetween($allShifts, $weekStart, $weekEnd);

            $weeklyPeriods[] = [
                'label' => $this->formatPeriodLabel($weekStart, $weekEnd),
                'start_date' => $weekStart->format('Y-m-d'),
                'end_date' => $weekEnd->format('Y-m-d'),
                'is_current_week' => $weekStart->isSameDay($startOfWeek),
                'hours_this_period' => $this->sumHours($periodShifts),
                'employees' => $this->buildEmployeeRows($allShifts, $periodShifts),
                'project_rows' => $this->buildProjectRows($allShifts, $periodShifts),
                'shifts' => $this->buildShiftRows($periodShifts),
                'days' => $this->buildDailySeries($periodShifts, $weekStart),
            ];
        }

        $currentWeekIndex = collect($weeklyPeriods)->search(fn ($week) => $week['is_current_week'] ?? false);
        if ($currentWeekIndex === false) {
            $currentWeekIndex = max(count($weeklyPeriods) - 1, 0);
        }

        return [
            'weekly_periods' => $weeklyPeriods,
            'current_week_index' => $currentWeekIndex,
            'org_projects' => $this->buildOrgProjects($allShifts),
            'org_stats' => $this->buildOrgStats($allShifts),
            'hours_timeline' => app(BuildHoursTimeline::class)(null),
            'projects' => Project::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                ])
                ->values()
                ->all(),
        ];
    }

    public function formatShiftRow(Shift $shift): array
    {
        $date = $shift->date instanceof Carbon
            ? $shift->date
            : Carbon::parse($shift->date);

        return [
            'id' => $shift->id,
            'netid' => $shift->netid,
            'employee_name' => $shift->user?->name ?? $shift->netid,
            'proj_id' => $shift->proj_id,
            'project_name' => $shift->project?->name ?? 'Unknown project',
            'date' => $date->format('Y-m-d'),
            'date_display' => $date->format('n/j/y'),
            'duration_minutes' => $shift->duration ?? 0,
            'hours' => round(($shift->duration ?? 0) / 60, 2),
            'entered' => (bool) $shift->entered,
            'billed' => (bool) $shift->billed,
        ];
    }

    private function buildShiftRows(Collection $periodShifts): array
    {
        return $periodShifts
            ->sortByDesc(function ($shift) {
                $date = $shift->date instanceof Carbon
                    ? $shift->date
                    : Carbon::parse($shift->date);

                return $date->format('Y-m-d') . str_pad((string) $shift->id, 8, '0', STR_PAD_LEFT);
            })
            ->values()
            ->map(fn (Shift $shift) => $this->formatShiftRow($shift))
            ->all();
    }

    private function buildEmployeeRows(Collection $allShifts, Collection $periodShifts): array
    {
        $netids = $allShifts->pluck('netid')->unique();

        return User::query()
            ->whereIn('netid', $netids)
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($allShifts, $periodShifts) {
                $userShifts = $allShifts->where('netid', $user->netid);
                $userPeriodShifts = $periodShifts->where('netid', $user->netid);
                $topProject = $this->topProjectForShifts($userShifts);
                $periodHours = app(ProjectHours::class)($userPeriodShifts);
                $totalHours = app(ProjectHours::class)($userShifts);

                return [
                    'netid' => $user->netid,
                    'name' => $user->name,
                    'unbilled_hours' => $periodHours['unbilled_hours'],
                    'total_hours' => $totalHours['total_hours'],
                    'top_project' => $topProject['name'],
                    'last_shift_date' => $this->lastShiftDate($userShifts),
                ];
            })
            ->filter(fn (array $row) => $row['unbilled_hours'] > 0 || $row['total_hours'] > 0)
            ->values()
            ->all();
    }

    private function buildProjectRows(Collection $allShifts, Collection $periodShifts): array
    {
        return $allShifts
            ->groupBy('proj_id')
            ->map(function (Collection $projectShifts) use ($periodShifts) {
                $first = $projectShifts->first();
                $projectId = $first->proj_id;
                $projectPeriodShifts = $periodShifts->where('proj_id', $projectId);
                $topEmployee = $this->topEmployeeForShifts($projectShifts);

                return [
                    'id' => $projectId,
                    'name' => $first->project?->name ?? 'Unknown project',
                    'hours_last_period' => $this->sumHours($projectPeriodShifts),
                    'total_hours' => $this->sumHours($projectShifts),
                    'top_employee' => $topEmployee['name'],
                    'last_shift_date' => $this->lastShiftDate($projectShifts),
                ];
            })
            ->filter(fn (array $row) => $row['hours_last_period'] > 0 || $row['total_hours'] > 0)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function buildDailySeries(Collection $periodShifts, Carbon $weekStart): array
    {
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $minutes = $periodShifts
                ->filter(function ($shift) use ($dateString) {
                    $shiftDate = $shift->date instanceof Carbon
                        ? $shift->date->format('Y-m-d')
                        : Carbon::parse($shift->date)->format('Y-m-d');

                    return $shiftDate === $dateString;
                })
                ->sum(fn ($shift) => $shift->duration ?? 0);

            $days[] = [
                'label' => strtoupper($date->format('D')),
                'date' => $dateString,
                'hours' => round($minutes / 60, 2),
            ];
        }

        return $days;
    }

    private function buildOrgProjects(Collection $allShifts): array
    {
        $projectHours = app(ProjectHours::class);

        return Project::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) use ($allShifts, $projectHours) {
                $shifts = $allShifts->where('proj_id', $project->id);
                $hours = $projectHours($shifts);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'billed_hours' => $hours['billed_hours'],
                    'unbilled_hours' => $hours['unbilled_hours'],
                ];
            })
            ->filter(fn (array $row) => $row['billed_hours'] > 0 || $row['unbilled_hours'] > 0)
            ->values()
            ->all();
    }

    private function buildOrgStats(Collection $allShifts): array
    {
        $totalHours = $this->sumHours($allShifts);

        $weekStarts = $allShifts->map(function ($shift) {
            $date = $shift->date instanceof Carbon
                ? $shift->date
                : Carbon::parse($shift->date);

            return $date->copy()->startOfWeek(self::WEEK_START)->format('Y-m-d');
        })->unique();

        $weeksWorked = $weekStarts->count();
        $avgHoursPerWeek = $weeksWorked > 0
            ? round($totalHours / $weeksWorked, 2)
            : 0.0;

        return [
            'total_hours' => $totalHours,
            'avg_hours_per_week' => $avgHoursPerWeek,
        ];
    }

    private function topProjectForShifts(Collection $shifts): array
    {
        if ($shifts->isEmpty()) {
            return ['id' => null, 'name' => '—'];
        }

        $top = $shifts
            ->groupBy('proj_id')
            ->map(fn (Collection $group) => [
                'id' => $group->first()->proj_id,
                'name' => $group->first()->project?->name ?? 'Unknown project',
                'hours' => $group->sum(fn ($shift) => $shift->duration ?? 0),
            ])
            ->sortByDesc('hours')
            ->first();

        return ['id' => $top['id'], 'name' => $top['name']];
    }

    private function topEmployeeForShifts(Collection $shifts): array
    {
        if ($shifts->isEmpty()) {
            return ['netid' => null, 'name' => '—'];
        }

        $top = $shifts
            ->groupBy('netid')
            ->map(fn (Collection $group) => [
                'netid' => $group->first()->netid,
                'name' => $group->first()->user?->name ?? $group->first()->netid,
                'hours' => $group->sum(fn ($shift) => $shift->duration ?? 0),
            ])
            ->sortByDesc('hours')
            ->first();

        return ['netid' => $top['netid'], 'name' => $top['name']];
    }

    private function lastShiftDate(Collection $shifts): ?string
    {
        $latest = $shifts
            ->map(fn ($shift) => $shift->date instanceof Carbon
                ? $shift->date
                : Carbon::parse($shift->date))
            ->sortDesc()
            ->first();

        return $latest?->format('n/j/y');
    }

    private function sumHours(Collection $shifts): float
    {
        return round($shifts->sum(fn ($shift) => $shift->duration ?? 0) / 60, 2);
    }

    private function filterShiftsBetween(Collection $shifts, Carbon $start, Carbon $end): Collection
    {
        return $shifts->filter(function ($shift) use ($start, $end) {
            $shiftDate = $shift->date instanceof Carbon
                ? $shift->date
                : Carbon::parse($shift->date);

            return $shiftDate->betweenIncluded($start, $end);
        });
    }

    private function formatPeriodLabel(Carbon $start, Carbon $end): string
    {
        return $start->format('M jS, Y') . ' - ' . $end->format('M jS, Y');
    }
}
