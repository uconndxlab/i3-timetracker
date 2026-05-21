<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayPeriod
{
    public const WEEK_START = Carbon::THURSDAY;

    public const WEEK_END = Carbon::WEDNESDAY;

    public static function currentWeekStart(): Carbon
    {
        return Carbon::now()->startOfWeek(self::WEEK_START);
    }

    public static function currentWeekEnd(): Carbon
    {
        return Carbon::now()->endOfWeek(self::WEEK_END);
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, start_date: string, end_date: string, label: string, is_current_week: bool}>
     */
    public static function buildWeeks(int $count): array
    {
        $startOfWeek = self::currentWeekStart();
        $firstWeekStart = $startOfWeek->copy()->subWeeks($count - 1);
        $weeks = [];

        for ($weekOffset = 0; $weekOffset < $count; $weekOffset++) {
            $weekStart = $firstWeekStart->copy()->addWeeks($weekOffset);
            $weekEnd = $weekStart->copy()->endOfWeek(self::WEEK_END);

            $weeks[] = [
                'start' => $weekStart,
                'end' => $weekEnd,
                'start_date' => $weekStart->format('Y-m-d'),
                'end_date' => $weekEnd->format('Y-m-d'),
                'label' => self::formatLabel($weekStart, $weekEnd),
                'is_current_week' => $weekStart->isSameDay($startOfWeek),
            ];
        }

        return $weeks;
    }

    public static function formatLabel(Carbon $start, Carbon $end): string
    {
        return $start->format('M jS, Y').' - '.$end->format('M jS, Y');
    }

    /**
     * @param  array<int|string, array{start_date?: string, is_current_week?: bool}>  $weeks
     */
    public static function resolveActiveIndex(array $weeks, ?string $periodStart): int
    {
        $currentWeekIndex = collect($weeks)->search(fn ($week) => $week['is_current_week'] ?? false);
        if ($currentWeekIndex === false) {
            $currentWeekIndex = max(count($weeks) - 1, 0);
        }

        if (! is_string($periodStart) || $periodStart === '') {
            return $currentWeekIndex;
        }

        $requestedIndex = collect($weeks)->search(
            fn ($week) => ($week['start_date'] ?? '') === $periodStart
        );

        return $requestedIndex !== false ? $requestedIndex : $currentWeekIndex;
    }

    /**
     * @param  Collection<string, int>|array<string, int>  $minutesByDate
     * @return list<array{label: string, date: string, hours: float}>
     */
    public static function buildDailySeries(Collection|array $minutesByDate, Carbon $weekStart): array
    {
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $days[] = [
                'label' => strtoupper($date->format('D')),
                'date' => $dateString,
                'hours' => round(((int) (is_array($minutesByDate)
                    ? ($minutesByDate[$dateString] ?? 0)
                    : ($minutesByDate[$dateString] ?? 0))) / 60, 2),
            ];
        }

        return $days;
    }

    public static function sumHoursFromDailyMinutes(Collection|array $minutesByDate, Carbon $weekStart, Carbon $weekEnd): float
    {
        $minutes = 0;

        for ($date = $weekStart->copy(); $date->lte($weekEnd); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $minutes += (int) (is_array($minutesByDate)
                ? ($minutesByDate[$dateString] ?? 0)
                : ($minutesByDate[$dateString] ?? 0));
        }

        return round($minutes / 60, 2);
    }

    public static function countWeeksWorked(Collection $dates): int
    {
        return $dates
            ->map(fn (string $date) => Carbon::parse($date)->startOfWeek(self::WEEK_START)->format('Y-m-d'))
            ->unique()
            ->count();
    }
}
