<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'netid',
        'proj_id',
        'date',
        'duration',
        'billed',
        'entered',
    ];

    protected $casts = [
        'date' => 'datetime',
        'billed' => 'boolean',
        'entered' => 'boolean',
        'duration' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'netid', 'netid');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'proj_id');
    }

    public function toUserRow(bool $isAdmin = false): array
    {
        $date = $this->date instanceof \Carbon\Carbon
            ? $this->date
            : \Carbon\Carbon::parse($this->date);
        $hours = round(($this->duration ?? 0) / 60, 2);

        return [
            'id' => $this->id,
            'proj_id' => $this->proj_id,
            'date' => $date->format('Y-m-d'),
            'duration_minutes' => $this->duration ?? 0,
            'duration_hours' => $hours,
            'entered' => (bool) $this->entered,
            'billed' => (bool) $this->billed,
            'can_edit' => $isAdmin || (! $this->entered && ! $this->billed),
            'project_name' => $this->project?->name ?? 'Unknown project',
        ];
    }

    public function toAdminRow(): array
    {
        return self::formatAdminRow($this);
    }

    public static function formatAdminRow(object $shift): array
    {
        $date = $shift->date instanceof \Carbon\Carbon
            ? $shift->date
            : \Carbon\Carbon::parse($shift->date);

        return [
            'id' => $shift->id,
            'netid' => $shift->netid,
            'employee_name' => $shift->employee_name ?? $shift->user?->name ?? $shift->netid,
            'proj_id' => $shift->proj_id,
            'project_name' => $shift->project_name ?? $shift->project?->name ?? 'Unknown project',
            'date' => $date->format('Y-m-d'),
            'date_display' => $date->format('n/j/y'),
            'duration_minutes' => $shift->duration ?? 0,
            'hours' => round(($shift->duration ?? 0) / 60, 2),
            'entered' => (bool) $shift->entered,
            'billed' => (bool) $shift->billed,
        ];
    }
}
