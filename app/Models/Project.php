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
}