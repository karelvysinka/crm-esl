<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'sku',
        'alt_code',
        'name',
        'name_alt',
        'qty',
        'unit_price',
        'unit_price_disc',
        'cost',
        'cost_disc',
        'discounts_card',
        'discounts_group',
        'product_group',
        'eshop_category_url',
        'tax_code',
        'currency',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'unit_price_disc' => 'decimal:4',
        'cost' => 'decimal:4',
        'cost_disc' => 'decimal:4',
        'discounts_card' => 'decimal:2',
        'discounts_group' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

}
