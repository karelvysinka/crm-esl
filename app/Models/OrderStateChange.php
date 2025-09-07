<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStateChange extends Model
{
    protected $fillable = [
        'order_id','old_code','new_code','changed_at','detected_at','source_snapshot_hash'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'detected_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
