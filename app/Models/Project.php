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
        'desc',
        'active',
    ];


    protected $casts = [
        'active' => 'boolean',
    ];

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'proj_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_netid', 'id', 'netid');
    }
}
