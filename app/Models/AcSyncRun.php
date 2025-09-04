<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcSyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'started_at','finished_at','limit','offset','created','updated','skipped','skipped_unchanged','errors','sample_created_ids','sample_updated_ids','message'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'sample_created_ids' => 'array',
        'sample_updated_ids' => 'array',
    ];
}
