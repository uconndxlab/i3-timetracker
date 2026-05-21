<?php

declare(strict_types=1);

namespace App\Actions\Shifts;

use App\Support\PayPeriod;
use Illuminate\Support\Facades\DB;

class BuildAllTimeStatistics
{
    public function __invoke(string $netid): array
    {
        $summary = DB::table('shifts')
            ->where('netid', $netid)
            ->selectRaw('COUNT(*) as total_shifts')
            ->selectRaw('COALESCE(SUM(duration), 0) as total_minutes')
            ->first();

        $totalShifts = (int) ($summary->total_shifts ?? 0);
        $totalHours = round(((int) ($summary->total_minutes ?? 0)) / 60, 2);

        $projects = DB::table('shifts')
            ->join('projects', 'shifts.proj_id', '=', 'projects.id')
            ->where('shifts.netid', $netid)
            ->select('shifts.proj_id')
            ->selectRaw('projects.name as name')
            ->selectRaw('COALESCE(SUM(shifts.duration), 0) / 60 as total_hours')
            ->selectRaw('COALESCE(SUM(CASE WHEN shifts.billed = 1 THEN shifts.duration ELSE 0 END), 0) / 60 as billed_hours')
            ->selectRaw('COALESCE(SUM(CASE WHEN shifts.billed = 0 THEN shifts.duration ELSE 0 END), 0) / 60 as unbilled_hours')
            ->groupBy('shifts.proj_id', 'projects.name')
            ->orderByDesc('total_hours')
            ->get()
            ->map(fn ($row) => [
                'proj_id' => $row->proj_id,
                'name' => $row->name,
                'hours' => round((float) $row->total_hours, 2),
                'billed_hours' => round((float) $row->billed_hours, 2),
                'unbilled_hours' => round((float) $row->unbilled_hours, 2),
            ])
            ->all();

        $weeksWorked = PayPeriod::countWeeksWorked(
            DB::table('shifts')->where('netid', $netid)->pluck('date')
        );

        $avgHoursPerWeek = $weeksWorked > 0
            ? round($totalHours / $weeksWorked, 2)
            : 0.0;

        return [
            'total_shifts' => $totalShifts,
            'total_hours' => $totalHours,
            'avg_hours_per_week' => $avgHoursPerWeek,
            'weeks_worked' => $weeksWorked,
            'projects' => $projects,
        ];
    }
}
