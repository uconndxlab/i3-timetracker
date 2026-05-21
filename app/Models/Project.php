<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    protected $casts = [
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

    public function scopeAssignedToUser($query, string $netid)
    {
        return $query->join('project_user', 'projects.id', '=', 'project_user.project_id')
            ->where('project_user.user_netid', $netid)
            ->select('projects.*')
            ->distinct();
    }
}
