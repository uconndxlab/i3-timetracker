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

    public function getTotalHoursAttribute(): float
    {
        return $this->duration / 60;
    }

    public function getUnbilledHoursAttribute(): float
    {
        return ! $this->billed ? ($this->duration / 60) : 0;
    }
}
