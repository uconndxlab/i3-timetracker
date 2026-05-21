<?php

declare(strict_types=1);

namespace App\Actions\Shifts;

use App\Support\PayPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BuildHoursTimeline
{
    public function __invoke(?string $netid = null): array
    {
        $now = Carbon::now();

        return [
            'month' => $this->buildMonthSeries($netid, $now),
            'week' => $this->buildCalendarWeekSeries($netid, $now),
            'year' => $this->buildYearSeries($netid, $now),
        ];
    }

    private function baseQuery(?string $netid)
    {
        return DB::table('shifts')->when($netid !== null, fn ($query) => $query->where('netid', $netid));
    }

    private function buildMonthSeries(?string $netid, Carbon $now): array
    {
        $start = $now->copy()->startOfMonth()->format('Y-m-d');
        $end = $now->copy()->endOfMonth()->format('Y-m-d');

        $minutesByDate = $this->baseQuery($netid)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('date, SUM(duration) as minutes')
            ->groupBy('date')
            ->pluck('minutes', 'date');

        $points = [];

        for ($date = $now->copy()->startOfMonth(); $date->lte($now->copy()->endOfMonth()); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $points[] = [
                'label' => $date->format('n/j'),
                'hours' => round(((int) ($minutesByDate[$dateString] ?? 0)) / 60, 2),
            ];
        }

        return $this->formatSeries($points);
    }

    private function buildCalendarWeekSeries(?string $netid, Carbon $now): array
    {
        $sunday = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $start = $sunday->format('Y-m-d');
        $end = $sunday->copy()->addDays(6)->format('Y-m-d');

        $minutesByDate = $this->baseQuery($netid)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('date, SUM(duration) as minutes')
            ->groupBy('date')
            ->pluck('minutes', 'date');

        $points = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $sunday->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');
            $points[] = [
                'label' => strtoupper($date->format('D')),
                'hours' => round(((int) ($minutesByDate[$dateString] ?? 0)) / 60, 2),
            ];
        }

        return $this->formatSeries($points);
    }

    private function buildYearSeries(?string $netid, Carbon $now): array
    {
        $start = $now->copy()->subMonths(11)->startOfMonth()->format('Y-m-d');
        $end = $now->copy()->endOfMonth()->format('Y-m-d');

        $minutesByMonth = $this->baseQuery($netid)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('SUBSTR(date, 1, 4) as year, SUBSTR(date, 6, 2) as month, SUM(duration) as minutes')
            ->groupBy('year', 'month')
            ->get()
            ->mapWithKeys(fn ($row) => [
                sprintf('%s-%s', $row->year, $row->month) => (int) $row->minutes,
            ]);

        $points = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $monthStart = $now->copy()->subMonths($offset)->startOfMonth();
            $key = $monthStart->format('Y-m');
            $points[] = [
                'label' => $monthStart->format('M'),
                'hours' => round(((int) ($minutesByMonth[$key] ?? 0)) / 60, 2),
            ];
        }

        return $this->formatSeries($points);
    }

    public function buildPeriodSeries(?string $netid, Carbon $periodStart, Carbon $periodEnd): array
    {
        $start = $periodStart->format('Y-m-d');
        $end = $periodEnd->format('Y-m-d');

        $minutesByDate = $this->baseQuery($netid)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('date, SUM(duration) as minutes')
            ->groupBy('date')
            ->pluck('minutes', 'date');

        $days = PayPeriod::buildDailySeries($minutesByDate, $periodStart->copy()->startOfWeek(PayPeriod::WEEK_START));

        return $this->formatSeries(array_map(fn (array $day) => [
            'label' => $day['label'],
            'hours' => $day['hours'],
        ], $days));
    }

    private function formatSeries(array $points): array
    {
        return [
            'labels' => array_column($points, 'label'),
            'data' => array_column($points, 'hours'),
            'total' => round(array_sum(array_column($points, 'hours')), 2),
        ];
    }
}
