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

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return ['active' => 'boolean',];
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'proj_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'proj_id', 'user_id')->withPivot('active')->withTimestamps();
    }
}
