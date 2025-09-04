<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpsActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'status', 'user_id', 'started_at', 'finished_at', 'duration_ms', 'meta', 'log_excerpt'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array'
    ];
}
