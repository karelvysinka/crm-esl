<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
	'order_number', 'order_created_at', 'total_vat_cents', 'currency', 'fetched_at', 'source_raw_hash', 'is_completed', 'last_state_code', 'external_edit_id'
    ];

    protected $casts = [
    'order_created_at' => 'datetime',
        'fetched_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stateChanges(): HasMany
    {
        return $this->hasMany(OrderStateChange::class);
    }
}
