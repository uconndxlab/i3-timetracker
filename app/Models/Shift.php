<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    protected $fillable = [
        'netid',
        'proj_id',
        'start_time',
        'end_time',
        'billed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
