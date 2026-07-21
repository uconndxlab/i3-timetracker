<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayPeriod
{
    public const WEEK_START = Carbon::FRIDAY;

    public const WEEK_END = Carbon::THURSDAY;

    public const WORK_WEEK_LENGTH_DAYS = 7;

    public const PERIOD_LENGTH_DAYS = 14;

    /** A known pay period end (Thursday) used to align bi-weekly boundaries. */
    public const PERIOD_ANCHOR_END = '2026-06-11';

    public static function currentWorkWeekStart(): Carbon
    {
        return Carbon::now()->startOfWeek(self::WEEK_START);
    }

    public static function currentWorkWeekEnd(): Carbon
    {
        return Carbon::now()->endOfWeek(self::WEEK_END);
    }

    public static function currentWeekStart(): Carbon
    {
        return self::currentWorkWeekStart();
    }

    public static function currentWeekEnd(): Carbon
    {
        return self::currentWorkWeekEnd();
    }

    public static function currentPeriodEnd(): Carbon
    {
        return self::periodEndForDate(Carbon::now());
    }

    public static function currentPeriodStart(): Carbon
    {
        return self::periodStartForEnd(self::currentPeriodEnd());
    }

    public static function periodEndForDate(Carbon $date): Carbon
    {
        $anchor = Carbon::parse(self::PERIOD_ANCHOR_END)->startOfDay();
        $target = $date->copy()->startOfDay();
        $daysSinceAnchor = $anchor->diffInDays($target, false);
        $periodNumber = (int) floor(($daysSinceAnchor + self::PERIOD_LENGTH_DAYS - 1) / self::PERIOD_LENGTH_DAYS);

        return $anchor->copy()->addDays($periodNumber * self::PERIOD_LENGTH_DAYS);
    }

    public static function periodStartForEnd(Carbon $periodEnd): Carbon
    {
        return $periodEnd->copy()->subDays(self::PERIOD_LENGTH_DAYS - 1)->startOfDay();
    }

    public static function isInCurrentPayPeriod(Carbon $date): bool
    {
        return $date->copy()->startOfDay()->betweenIncluded(
            self::currentPeriodStart(),
            self::currentPeriodEnd(),
        );
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, start_date: string, end_date: string, label: string, is_current_week: bool}>
     */
    public static function buildWeeks(int $count): array
    {
        $startOfWeek = self::currentWorkWeekStart();
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
     * @param  array<int|string, array{is_current_week?: bool}>  $weeks
     */
    public static function resolveActiveIndex(array $weeks): int
    {
        $currentWeekIndex = collect($weeks)->search(fn ($week) => $week['is_current_week'] ?? false);
        if ($currentWeekIndex === false) {
            $currentWeekIndex = max(count($weeks) - 1, 0);
        }

        return $currentWeekIndex;
    }

    /**
     * @param  Collection<string, int>|array<string, int>  $minutesByDate
     * @return list<array{label: string, date: string, hours: float}>
     */
    public static function buildDailySeries(Collection|array $minutesByDate, Carbon $weekStart): array
    {
        $days = [];

        for ($i = 0; $i < self::WORK_WEEK_LENGTH_DAYS; $i++) {
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
