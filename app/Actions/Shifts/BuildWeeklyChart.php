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
            ->whereBetween('date', [$firstWeekStart->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
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
            $totalMinutesForWeek = $weekShifts->sum(fn ($shift) => $shift->duration ?? 0);

            $weeklyChartData[] = [
                'label' => $week['label'],
                'start_date' => $week['start_date'],
                'end_date' => $week['end_date'],
                'hours_this_week' => round($totalMinutesForWeek / 60, 2),
                'days' => $days,
                'daily_hours' => collect($days)->mapWithKeys(fn ($day) => [$day['key'] => $day['hours']])->all(),
                'shifts' => $this->buildShiftCards($weekShifts, $isAdmin),
                'is_current_week' => $week['is_current_week'],
            ];
        }

        $currentWeek = collect($weeklyChartData)->firstWhere('is_current_week')
            ?? $weeklyChartData[array_key_last($weeklyChartData)];

        $currentWeekIndex = PayPeriod::resolveActiveIndex($weeklyChartData, null);

        return [
            'hoursThisWeek' => $currentWeek['hours_this_week'] ?? 0,
            'weeklyChartData' => $weeklyChartData,
            'currentWeekIndex' => $currentWeekIndex,
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

            $dayMinutes = $dayShifts->sum(fn ($shift) => $shift->duration ?? 0);

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
                'label' => $date->format('l'),
                'date' => $dateString,
                'date_display' => $date->format('M j'),
                'date_badge' => strtoupper($date->format('M j')),
                'weekday' => strtoupper($date->format('l')),
                'hours' => round($dayMinutes / 60, 2),
                'project_hours' => $projectHours,
                'shifts' => $dayShifts->map(fn ($shift) => $this->formatShift($shift, $isAdmin))->all(),
            ];
        }

        return $days;
    }

    private function buildShiftCards($weekShifts, bool $isAdmin = false): array
    {
        return $weekShifts
            ->sortByDesc(fn ($shift) => $shift->date)
            ->values()
            ->map(fn ($shift) => $this->formatShift($shift, $isAdmin))
            ->all();
    }

    private function formatShift($shift, bool $isAdmin): array
    {
        $date = $shift->date instanceof Carbon
            ? $shift->date
            : Carbon::parse($shift->date);

        $hours = round(($shift->duration ?? 0) / 60, 2);

        return [
            'id' => $shift->id,
            'proj_id' => $shift->proj_id,
            'date' => $date->format('Y-m-d'),
            'date_badge' => strtoupper($date->format('M j')),
            'weekday' => strtoupper($date->format('l')),
            'duration_minutes' => $shift->duration ?? 0,
            'duration_hours' => $hours,
            'duration_display' => number_format($hours, 2) . ' hr',
            'entered' => (bool) $shift->entered,
            'billed' => (bool) $shift->billed,
            'can_edit' => $isAdmin || (!(bool) $shift->entered && !(bool) $shift->billed),
            'project_name' => $shift->project?->name ?? 'Unknown project',
            'project_description' => $shift->project?->description ?? '',
        ];
    }
}
