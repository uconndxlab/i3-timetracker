<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildProjectOverview
{
    public function __invoke(Project $project, ?string $periodStart = null): array
    {
        $allTime = $this->loadHoursSummary($project->id);

        // Keep the overview fast: show the most recent shifts, but keep all billing stats all-time.
        $shiftLimit = (int) request()->query('shift_limit', 200);
        $shiftLimit = $shiftLimit > 0 ? $shiftLimit : 200;

        $projectShifts = $this->loadProjectShiftsAllTime($project->id, $shiftLimit);

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'active' => $project->active,
            ],
            'all_time' => $allTime,
            'employees' => $this->buildEmployeeRowsAllTime($project->id),
            'shifts' => $this->buildShiftRows($projectShifts),
            'assigned_users' => $project->users()
                ->orderBy('name')
                ->get(['users.netid', 'users.name'])
                ->map(fn (User $user) => [
                    'netid' => $user->netid,
                    'name' => $user->name,
                ])
                ->values()
                ->all(),
        ];
    }

    private function loadHoursSummary(int $projectId, ?string $rangeStart = null, ?string $rangeEnd = null): array
    {
        $query = DB::table('shifts')->where('proj_id', $projectId);

        if ($rangeStart !== null && $rangeEnd !== null) {
            $query->whereDate('date', '>=', $rangeStart)
                ->whereDate('date', '<=', $rangeEnd);
        }

        $row = $query
            ->selectRaw('COALESCE(SUM(duration), 0) as total_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 1 THEN duration ELSE 0 END), 0) as billed_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN billed = 0 THEN duration ELSE 0 END), 0) as unbilled_minutes')
            ->selectRaw('COUNT(*) as shift_count')
            ->first();

        $totalMinutes = (int) ($row->total_minutes ?? 0);
        $billedMinutes = (int) ($row->billed_minutes ?? 0);
        $unbilledMinutes = (int) ($row->unbilled_minutes ?? 0);

        return [
            'total_hours' => round($totalMinutes / 60, 2),
            'billed_hours' => round($billedMinutes / 60, 2),
            'unbilled_hours' => round($unbilledMinutes / 60, 2),
            'shift_count' => (int) ($row->shift_count ?? 0),
        ];
    }

    private function loadProjectShifts(int $projectId, string $rangeStart, string $rangeEnd): Collection
    {
        return DB::table('shifts')
            ->join('users', 'shifts.netid', '=', 'users.netid')
            ->where('shifts.proj_id', $projectId)
            ->whereDate('shifts.date', '>=', $rangeStart)
            ->whereDate('shifts.date', '<=', $rangeEnd)
            ->select([
                'shifts.id',
                'shifts.netid',
                'shifts.proj_id',
                'shifts.date',
                'shifts.duration',
                'shifts.entered',
                'shifts.billed',
                'users.name as employee_name',
            ])
            ->orderByDesc('shifts.date')
            ->orderByDesc('shifts.id')
            ->get();
    }

    private function loadProjectShiftsAllTime(int $projectId, int $limit): Collection
    {
        return DB::table('shifts')
            ->join('users', 'shifts.netid', '=', 'users.netid')
            ->join('projects', 'shifts.proj_id', '=', 'projects.id')
            ->where('shifts.proj_id', $projectId)
            ->select([
                'shifts.id',
                'shifts.netid',
                'shifts.proj_id',
                'shifts.date',
                'shifts.duration',
                'shifts.entered',
                'shifts.billed',
                'users.name as employee_name',
                'projects.name as project_name',
            ])
            ->orderByDesc('shifts.date')
            ->orderByDesc('shifts.id')
            ->limit($limit)
            ->get();
    }

    private function buildEmployeeRowsAllTime(int $projectId): array
    {
        $rows = DB::table('shifts')
            ->join('users', 'shifts.netid', '=', 'users.netid')
            ->where('shifts.proj_id', $projectId)
            ->select('shifts.netid', 'users.name as employee_name')
            ->selectRaw('COALESCE(SUM(shifts.duration), 0) as total_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN shifts.billed = 1 THEN shifts.duration ELSE 0 END), 0) as billed_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN shifts.billed = 0 THEN shifts.duration ELSE 0 END), 0) as unbilled_minutes')
            ->selectRaw('MAX(shifts.date) as last_date')
            ->groupBy('shifts.netid', 'users.name')
            ->orderBy('users.name')
            ->get();

        return $rows
            ->map(function ($row) {
                $lastShiftDate = isset($row->last_date)
                    ? Carbon::parse($row->last_date)->format('n/j/y')
                    : null;

                $totalHours = round(((int) ($row->total_minutes ?? 0)) / 60, 2);
                $billedHours = round(((int) ($row->billed_minutes ?? 0)) / 60, 2);
                $unbilledHours = round(((int) ($row->unbilled_minutes ?? 0)) / 60, 2);

                return [
                    'netid' => $row->netid,
                    'name' => $row->employee_name ?? $row->netid,
                    'billed_hours' => $billedHours,
                    'unbilled_hours' => $unbilledHours,
                    'total_hours' => $totalHours,
                    'last_shift_date' => $lastShiftDate,
                ];
            })
            ->filter(fn (array $row) => $row['total_hours'] > 0)
            ->values()
            ->all();
    }

    private function buildShiftRows(Collection $periodShifts): array
    {
        return $periodShifts
            ->map(fn ($shift) => Shift::formatAdminRow($shift))
            ->all();
    }

    private function buildEmployeeRows(int $projectId, Collection $periodShifts): array
    {
        $periodByUser = $periodShifts
            ->groupBy('netid')
            ->map(function (Collection $shifts) {
                $billedMinutes = $shifts->where('billed', true)->sum(fn ($s) => $s->duration ?? 0);
                $unbilledMinutes = $shifts->where('billed', false)->sum(fn ($s) => $s->duration ?? 0);

                return [
                    'period_hours' => round($shifts->sum(fn ($s) => $s->duration ?? 0) / 60, 2),
                    'period_billed_hours' => round($billedMinutes / 60, 2),
                    'period_unbilled_hours' => round($unbilledMinutes / 60, 2),
                ];
            });

        $allTimeByUser = DB::table('shifts')
            ->join('users', 'shifts.netid', '=', 'users.netid')
            ->where('shifts.proj_id', $projectId)
            ->select('shifts.netid', 'users.name')
            ->selectRaw('COALESCE(SUM(shifts.duration), 0) as total_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN shifts.billed = 1 THEN shifts.duration ELSE 0 END), 0) as billed_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN shifts.billed = 0 THEN shifts.duration ELSE 0 END), 0) as unbilled_minutes')
            ->groupBy('shifts.netid', 'users.name')
            ->orderBy('users.name')
            ->get();

        return $allTimeByUser
            ->map(function ($row) use ($periodByUser) {
                $period = $periodByUser->get($row->netid, [
                    'period_hours' => 0.0,
                    'period_billed_hours' => 0.0,
                    'period_unbilled_hours' => 0.0,
                ]);

                return [
                    'netid' => $row->netid,
                    'name' => $row->name,
                    'total_hours' => round(((int) $row->total_minutes) / 60, 2),
                    'billed_hours' => round(((int) $row->billed_minutes) / 60, 2),
                    'unbilled_hours' => round(((int) $row->unbilled_minutes) / 60, 2),
                    'period_hours' => $period['period_hours'],
                    'period_billed_hours' => $period['period_billed_hours'],
                    'period_unbilled_hours' => $period['period_unbilled_hours'],
                ];
            })
            ->filter(fn (array $row) => $row['total_hours'] > 0 || $row['period_hours'] > 0)
            ->values()
            ->all();
    }
}
