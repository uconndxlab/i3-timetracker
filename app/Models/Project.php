<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Shift;

class Project extends Model
{
    protected $fillable = [
        'name',
        'desc',
        'active',
    ];

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'proj_id');
    }
}
