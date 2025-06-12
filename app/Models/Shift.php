<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Project;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'netid',
        'proj_id',
        'start_time',
        'end_time',
        'billed',
        'entered',
    ];

    protected function casts(): array 
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'billed' => 'boolean',
            'entered' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'netid', 'netid');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'proj_id');
    }
}