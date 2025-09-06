<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceChange extends Model
{
    public $timestamps = false; // using changed_at
    protected $fillable = ['product_id','old_price_cents','new_price_cents','changed_at'];
    protected $casts = ['changed_at' => 'datetime'];
    public function product(){ return $this->belongsTo(Product::class); }
}
