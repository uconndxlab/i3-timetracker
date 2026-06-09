<?php

namespace App\Actions\Shifts;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildAnnualView
{
    public function __invoke(string $netid, int $year, string $selectedDate, bool $isAdmin = false): array
    {
        $yearStart = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $yearEnd   = Carbon::createFromDate($year, 12, 31)->endOfDay();

        $shifts = Shift::where('netid', $netid)
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->with('project')
            ->get();

        // Group by date string → sum minutes
        $grouped = $shifts->groupBy(fn (Shift $s) => Carbon::parse($s->date)->format('Y-m-d'));

        $dailyTotals = $grouped->map(function (Collection $dayShifts, string $date) {
            $minutes = $dayShifts->sum('duration');

            return [
                'minutes' => $minutes,
                'shade'   => $this->shade($minutes),
            ];
        })->all();

        // Shifts for the selected day
        $selectedDayShifts = ($grouped[$selectedDate] ?? collect())
            ->map(fn (Shift $s) => $s->toUserRow($isAdmin))
            ->values()
            ->all();

        $totalMinutes = $shifts->sum('duration');

        return [
            'dailyTotals'       => $dailyTotals,
            'selectedDayShifts' => $selectedDayShifts,
            'totalShiftsInYear' => $shifts->count(),
            'totalHoursInYear'  => round($totalMinutes / 60, 2),
        ];
    }

    private function shade(int $minutes): string
    {
        if ($minutes >= 420) {
            return 'dark';
        }

        if ($minutes >= 240) {
            return 'medium';
        }

        if ($minutes > 0) {
            return 'light';
        }

        return 'empty';
    }
}
