<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'order_id','external_item_id','name','product_code','variant_code','specification','quantity','unit','unit_price_vat_cents','vat_rate_percent','discount_percent','total_ex_vat_cents','total_vat_cents','line_type','currency'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_vat_cents' => 'integer',
        'vat_rate_percent' => 'integer',
        'discount_percent' => 'integer',
        'total_ex_vat_cents' => 'integer',
        'total_vat_cents' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
