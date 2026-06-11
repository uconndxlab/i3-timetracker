<?php

namespace App\Actions\Shifts;

use App\Models\Shift;
use App\Support\PayPeriod;
use Carbon\Carbon;

class BuildWeeklyChart
{
    public function __invoke(string $netid, int $weekCount = 20, bool $isAdmin = false): array
    {
        $weeks = PayPeriod::buildWeeks($weekCount);
        $firstWeekStart = $weeks[0]['start'];
        $endOfWeek = PayPeriod::currentWeekEnd();

        $shiftsInRange = Shift::where('netid', $netid)
            ->with('project')
            ->whereDate('date', '>=', $firstWeekStart->format('Y-m-d'))
            ->whereDate('date', '<=', $endOfWeek->format('Y-m-d'))
            ->get();

        $weeklyChartData = [];

        foreach ($weeks as $week) {
            $weekStart = $week['start'];
            $weekEnd = $week['end'];

            $weekShifts = $shiftsInRange->filter(function ($shift) use ($weekStart, $weekEnd) {
                $shiftDate = $shift->date instanceof Carbon
                    ? $shift->date
                    : Carbon::parse($shift->date);

                return $shiftDate->betweenIncluded($weekStart, $weekEnd);
            });

            $days = $this->buildDays($weekStart, $weekShifts, $isAdmin);

            $weeklyChartData[] = [
                'label' => $week['label'],
                'start_date' => $week['start_date'],
                'end_date' => $week['end_date'],
                'hours_this_week' => round($weekShifts->sum(fn ($shift) => $shift->duration ?? 0) / 60, 2),
                'days' => $days,
                'is_current_week' => $week['is_current_week'],
            ];
        }

        return [
            'weeklyChartData' => $weeklyChartData,
            'currentWeekIndex' => PayPeriod::resolveActiveIndex($weeklyChartData, null),
        ];
    }

    public function buildDay(string $netid, string $dateString, bool $isAdmin = false): array
    {
        $dayShifts = Shift::query()
            ->where('netid', $netid)
            ->with('project')
            ->whereDate('date', $dateString)
            ->get()
            ->sortBy('project.name')
            ->values();

        return $this->formatDay(Carbon::parse($dateString), $dayShifts, $isAdmin);
    }

    /**
     * @return array{start_date: string, hours_this_week: float}
     */
    public function buildWeekSummary(string $netid, string $dateString): array
    {
        $weekStart = Carbon::parse($dateString)->startOfWeek(PayPeriod::WEEK_START);
        $weekEnd = $weekStart->copy()->endOfWeek(PayPeriod::WEEK_END);
        $totalMinutes = (int) Shift::query()
            ->where('netid', $netid)
            ->whereDate('date', '>=', $weekStart->format('Y-m-d'))
            ->whereDate('date', '<=', $weekEnd->format('Y-m-d'))
            ->sum('duration');

        return [
            'start_date' => $weekStart->format('Y-m-d'),
            'hours_this_week' => round($totalMinutes / 60, 2),
        ];
    }

    private function buildDays(Carbon $weekStart, $weekShifts, bool $isAdmin = false): array
    {
        $days = [];

        for ($i = 0; $i < PayPeriod::WORK_WEEK_LENGTH_DAYS; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $dayShifts = $weekShifts->filter(function ($shift) use ($dateString) {
                $shiftDate = $shift->date instanceof Carbon
                    ? $shift->date->format('Y-m-d')
                    : Carbon::parse($shift->date)->format('Y-m-d');

                return $shiftDate === $dateString;
            })->sortBy('project.name')->values();

            $days[] = $this->formatDay($date, $dayShifts, $isAdmin);
        }

        return $days;
    }

    private function formatDay(Carbon $date, $dayShifts, bool $isAdmin = false): array
    {
        $dateString = $date->format('Y-m-d');

        $projectHours = $dayShifts
            ->groupBy('proj_id')
            ->map(function ($projectShifts, $projId) {
                $minutes = $projectShifts->sum(fn ($shift) => $shift->duration ?? 0);

                return [
                    'proj_id' => $projId,
                    'project_name' => $projectShifts->first()->project?->name ?? 'Unknown project',
                    'hours' => round($minutes / 60, 2),
                    'entered' => $projectShifts->every(fn ($shift) => (bool) $shift->entered),
                    'shift_ids' => $projectShifts->pluck('id')->values()->all(),
                ];
            })
            ->sortByDesc('hours')
            ->values()
            ->all();

        return [
            'key' => $date->format('D'),
            'date' => $dateString,
            'date_badge' => strtoupper($date->format('M j')),
            'weekday' => strtoupper($date->format('l')),
            'hours' => round($dayShifts->sum(fn ($shift) => $shift->duration ?? 0) / 60, 2),
            'project_hours' => $projectHours,
            'shifts' => $dayShifts->map(fn ($shift) => $shift->toUserRow($isAdmin))->all(),
        ];
    }
}
