<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductGroup extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_alt',
        'eshop_url',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductGroup::class, 'parent_id');
    }
}
