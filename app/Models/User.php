<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'netid',
        'email',
        'active',
    ];

    protected $guarded = [
        'is_admin',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_netid', 'project_id', 'netid', 'id')
            ->withPivot('active')
            ->withTimestamps();
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'netid', 'netid');
    }
}
