<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Shift;
use App\Models\User;


class Project extends Model
{
    #public $timestamps = false;
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    // protected function casts(): array 
    // {
    //     return [
    //         'start_time' => 'datetime',
    //         'end_time' => 'datetime',
    //         'billed' => 'boolean',
    //         'entered' => 'boolean',
    //     ];
    // }
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'billed' => 'boolean',
        'entered' => 'boolean',
        'active' => 'boolean', 
    ];


    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_netid', 'id', 'netid');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'proj_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'proj_id');
    }
    
    public function getDate()
    {
        return $this->start_time->format('M j, Y');
    }
    
    public function getRange()
    {
        return $this->start_time->format('g A') . ' - ' . $this->end_time->format('g A');
    }
    
    public function getDuration()
    {
        return round($this->start_time->diffInMinutes($this->end_time) / 60, 1);
    }

    public function getHoursForUser($netid)
    {
        $userShifts = $this->shifts()->where('netid', $netid)->get();
        
        $totalHours = 0;
        $billedHours = 0;
        $unbilledHours = 0;
        
        foreach ($userShifts as $shift) {
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
            'unbilled_hours' => round($unbilledHours, 2)
        ];
    }

    public function getAllHours()
    {
        $allShifts = $this->shifts()->get();
        
        $totalHours = 0;
        $billedHours = 0;
        $unbilledHours = 0;
        
        foreach ($allShifts as $shift) {
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
            'unbilled_hours' => round($unbilledHours, 2)
        ];
    }

    public function scopeAssignedToUser($query, $netid)
    {
        return $query->join('project_user', 'projects.id', '=', 'project_user.project_id')
            ->where('project_user.user_netid', $netid)
            ->select('projects.*')
            ->distinct();
    }
}