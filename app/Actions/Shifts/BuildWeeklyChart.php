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

    private function buildDays(Carbon $weekStart, $weekShifts, bool $isAdmin = false): array
    {
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $dayShifts = $weekShifts->filter(function ($shift) use ($dateString) {
                $shiftDate = $shift->date instanceof Carbon
                    ? $shift->date->format('Y-m-d')
                    : Carbon::parse($shift->date)->format('Y-m-d');

                return $shiftDate === $dateString;
            })->sortBy('project.name')->values();

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

            $days[] = [
                'key' => $date->format('D'),
                'date' => $dateString,
                'date_badge' => strtoupper($date->format('M j')),
                'weekday' => strtoupper($date->format('l')),
                'hours' => round($dayShifts->sum(fn ($shift) => $shift->duration ?? 0) / 60, 2),
                'project_hours' => $projectHours,
                'shifts' => $dayShifts->map(fn ($shift) => $shift->toUserRow($isAdmin))->all(),
            ];
        }

        return $days;
    }
}
