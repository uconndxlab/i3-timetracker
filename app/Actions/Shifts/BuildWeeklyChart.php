<?php

namespace App\Actions\Shifts;

use App\Models\Shift;
use Carbon\Carbon;

class BuildWeeklyChart
{
    public function __invoke(string $netid, int $weekCount = 20): array
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        $shiftsThisWeek = Shift::where('netid', $netid)
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->get();

        $totalMinutesThisWeek = $shiftsThisWeek->reduce(function ($carry, $shift) {
            return $carry + ($shift->duration ?? 0);
        }, 0);

        $hoursThisWeek = round($totalMinutesThisWeek / 60, 2);

        $dailyHours = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $dayMinutes = $shiftsThisWeek->filter(function ($shift) use ($dateString) {
                $shiftDate = $shift->date ? $shift->date->format('Y-m-d') : $shift->date;
                return $shiftDate === $dateString;
            })->sum('duration');

            $dailyHours[$date->format('D')] = round($dayMinutes / 60, 2);
        }

        $firstWeekStart = $startOfWeek->copy()->subWeeks($weekCount - 1);

        $shiftsInRange = Shift::where('netid', $netid)
            ->whereBetween('date', [$firstWeekStart->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->get();

        $dayKeys = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $weeklyChartData = [];

        for ($weekOffset = 0; $weekOffset < $weekCount; $weekOffset++) {
            $weekStart = $firstWeekStart->copy()->addWeeks($weekOffset);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);

            $weekShifts = $shiftsInRange->filter(function ($shift) use ($weekStart, $weekEnd) {
                $shiftDate = $shift->date instanceof Carbon
                    ? $shift->date
                    : Carbon::parse($shift->date);

                return $shiftDate->betweenIncluded($weekStart, $weekEnd);
            });

            $weekDailyHours = [];
            foreach ($dayKeys as $index => $dayKey) {
                $targetDate = $weekStart->copy()->addDays($index)->format('Y-m-d');

                $dayMinutes = $weekShifts->filter(function ($shift) use ($targetDate) {
                    $shiftDate = $shift->date instanceof Carbon
                        ? $shift->date->format('Y-m-d')
                        : Carbon::parse($shift->date)->format('Y-m-d');

                    return $shiftDate === $targetDate;
                })->sum(function ($shift) {
                    return $shift->duration ?? 0;
                });

                $weekDailyHours[$dayKey] = round($dayMinutes / 60, 2);
            }

            $totalMinutesForWeek = $weekShifts->sum(function ($shift) {
                return $shift->duration ?? 0;
            });

            $weeklyChartData[] = [
                'label' => $weekStart->format('M j') . ' - ' . $weekEnd->format('M j'),
                'start_date' => $weekStart->format('Y-m-d'),
                'end_date' => $weekEnd->format('Y-m-d'),
                'daily_hours' => $weekDailyHours,
                'hours_this_week' => round($totalMinutesForWeek / 60, 2),
            ];
        }

        return [
            'hoursThisWeek' => $hoursThisWeek,
            'dailyHours' => $dailyHours,
            'weeklyChartData' => $weeklyChartData,
        ];
    }
}
