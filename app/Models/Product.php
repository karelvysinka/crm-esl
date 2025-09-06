<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'external_id','group_id','name','description','price_vat_cents','currency','manufacturer','ean',
        'category_path','category_hash','url','image_url','availability_code','availability_text',
        'stock_quantity','availability_synced_at','hash_payload','first_imported_at','last_synced_at',
        'last_price_changed_at','last_availability_changed_at'
    ];

    protected $casts = [
        'availability_synced_at' => 'datetime',
        'first_imported_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_price_changed_at' => 'datetime',
        'last_availability_changed_at' => 'datetime',
    ];

    // Relationships
    public function priceChanges() { return $this->hasMany(ProductPriceChange::class); }
    public function availabilityChanges() { return $this->hasMany(ProductAvailabilityChange::class); }

    // Scopes
    public function scopeFilterCategory(Builder $q, ?string $hash): Builder
    { return $hash ? $q->where('category_hash', $hash) : $q; }

    public function scopeFilterAvailability(Builder $q, ?string $code): Builder
    { return $code ? $q->where('availability_code', $code) : $q; }

    public function scopeFilterManufacturer(Builder $q, ?string $m): Builder
    { return $m ? $q->where('manufacturer', $m) : $q; }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if(!$term) return $q;
        $like = '%'.strtolower($term).'%';
        return $q->where(function(Builder $qq) use ($like){
            $qq->whereRaw('LOWER(name) LIKE ?', [$like])
               ->orWhereRaw('LOWER(ean) LIKE ?', [$like])
               ->orWhereRaw('LOWER(external_id) LIKE ?', [$like]);
        });
    }
}
