<?php

namespace App\Actions\Shifts;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildHoursTimeline
{
    private const PERIOD_START = Carbon::THURSDAY;

    private const PERIOD_END = Carbon::WEDNESDAY;

    public function __invoke(?string $netid = null): array
    {
        $shifts = $netid === null
            ? Shift::query()->get()
            : Shift::where('netid', $netid)->get();
        $now = Carbon::now();

        return [
            'month' => $this->buildMonthSeries($shifts, $now),
            'week' => $this->buildCalendarWeekSeries($shifts, $now),
            'year' => $this->buildYearSeries($shifts, $now),
        ];
    }

    private function buildMonthSeries(Collection $shifts, Carbon $now): array
    {
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();
        $points = $this->buildDailyPoints($shifts, $start, $end);

        return $this->formatSeries($points);
    }

    private function buildCalendarWeekSeries(Collection $shifts, Carbon $now): array
    {
        $sunday = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $points = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $sunday->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');
            $minutes = $this->filterShiftsOnDate($shifts, $dateString)
                ->sum(fn ($shift) => $shift->duration ?? 0);

            $points[] = [
                'label' => strtoupper($date->format('D')),
                'hours' => round($minutes / 60, 2),
            ];
        }

        return $this->formatSeries($points);
    }

    public function buildCurrentPeriodSeries(Collection $shifts, Carbon $now): array
    {
        $start = $now->copy()->startOfWeek(self::PERIOD_START);
        $end = $now->copy()->endOfWeek(self::PERIOD_END);

        return $this->buildPeriodSeries($shifts, $start, $end);
    }

    private function buildYearSeries(Collection $shifts, Carbon $now): array
    {
        $points = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $monthStart = $now->copy()->subMonths($offset)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $minutes = $this->filterShiftsBetween($shifts, $monthStart, $monthEnd)
                ->sum(fn ($shift) => $shift->duration ?? 0);

            $points[] = [
                'label' => $monthStart->format('M'),
                'hours' => round($minutes / 60, 2),
            ];
        }

        return $this->formatSeries($points);
    }

    public function buildPeriodSeries(Collection $shifts, Carbon $periodStart, Carbon $periodEnd): array
    {
        $points = [];

        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $minutes = $this->filterShiftsOnDate($shifts, $dateString)
                ->sum(fn ($shift) => $shift->duration ?? 0);

            $points[] = [
                'label' => strtoupper($date->format('D')),
                'hours' => round($minutes / 60, 2),
            ];
        }

        return $this->formatSeries($points);
    }

    private function buildDailyPoints(Collection $shifts, Carbon $start, Carbon $end): array
    {
        $points = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $minutes = $this->filterShiftsOnDate($shifts, $dateString)
                ->sum(fn ($shift) => $shift->duration ?? 0);

            $points[] = [
                'label' => $date->format('n/j'),
                'hours' => round($minutes / 60, 2),
            ];
        }

        return $points;
    }

    private function formatSeries(array $points): array
    {
        return [
            'labels' => array_column($points, 'label'),
            'data' => array_column($points, 'hours'),
            'total' => round(array_sum(array_column($points, 'hours')), 2),
        ];
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

    private function filterShiftsOnDate(Collection $shifts, string $dateString): Collection
    {
        return $shifts->filter(function ($shift) use ($dateString) {
            $shiftDate = $shift->date instanceof Carbon
                ? $shift->date->format('Y-m-d')
                : Carbon::parse($shift->date)->format('Y-m-d');

            return $shiftDate === $dateString;
        });
    }
}
