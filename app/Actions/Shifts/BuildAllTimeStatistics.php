<?php

namespace App\Actions\Shifts;

use App\Actions\Projects\ProjectHours;
use App\Models\Shift;
use Carbon\Carbon;

class BuildAllTimeStatistics
{
    private const WEEK_START = Carbon::THURSDAY;

    public function __invoke(string $netid): array
    {
        $shifts = Shift::where('netid', $netid)->with('project')->get();

        $totalShifts = $shifts->count();
        $totalHours = round($shifts->sum(fn ($shift) => $shift->duration ?? 0) / 60, 2);

        $projectHours = app(ProjectHours::class);

        $projects = $shifts->groupBy('proj_id')
            ->map(function ($group) use ($projectHours) {
                $first = $group->first();
                $hours = $projectHours($group);

                return [
                    'proj_id' => $first->proj_id,
                    'name' => $first->project?->name ?? 'Unknown project',
                    'hours' => $hours['total_hours'],
                    'billed_hours' => $hours['billed_hours'],
                    'unbilled_hours' => $hours['unbilled_hours'],
                ];
            })
            ->sortByDesc('hours')
            ->values()
            ->all();

        $weekStarts = $shifts->map(function ($shift) {
            $date = $shift->date instanceof Carbon
                ? $shift->date
                : Carbon::parse($shift->date);

            return $date->copy()->startOfWeek(self::WEEK_START)->format('Y-m-d');
        })->unique();

        $weeksWorked = $weekStarts->count();
        $avgHoursPerWeek = $weeksWorked > 0
            ? round($totalHours / $weeksWorked, 2)
            : 0;

        return [
            'total_shifts' => $totalShifts,
            'total_hours' => $totalHours,
            'avg_hours_per_week' => $avgHoursPerWeek,
            'weeks_worked' => $weeksWorked,
            'projects' => $projects,
        ];
    }
}
