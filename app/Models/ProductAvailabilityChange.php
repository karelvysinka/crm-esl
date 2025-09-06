<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAvailabilityChange extends Model
{
    public $timestamps = false; // using changed_at
    protected $fillable = ['product_id','old_code','new_code','old_stock_qty','new_stock_qty','changed_at'];
    protected $casts = ['changed_at' => 'datetime'];
    public function product(){ return $this->belongsTo(Product::class); }
}
