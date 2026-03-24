<?php

namespace App\Actions\Projects;

class ProjectHours
{
    public function __invoke($shifts): array
    {
        $totalHours = 0;
        $billedHours = 0;
        $unbilledHours = 0;

        foreach ($shifts as $shift) {
            $hours = $shift->duration ? $shift->duration / 60 : 0;
            $totalHours += $hours;

            if ($shift->billed) {
                $billedHours += $hours;
            } else {
                $unbilledHours += $hours;
            }
        }

        return [
            'total_hours' => round($totalHours, 2),
            'billed_hours' => round($billedHours, 2),
            'unbilled_hours' => round($unbilledHours, 2),
        ];
    }
}
