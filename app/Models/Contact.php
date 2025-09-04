<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Contact extends Model
{
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
    'normalized_email',
        'phone',
    'normalized_phone',
        'mobile',
        'position',
        'department',
        'status',
    'marketing_status',
        'birthday',
        'address',
        'city',
        'country',
        'notes',
        'social_links',
        'preferred_contact',
        'created_by',
    'last_contacted_at',
    'ac_id',
    'ac_hash',
    'ac_updated_at',
    'legacy_external_id',
    ];

    protected $casts = [
        'social_links' => 'array',
        'birthday' => 'date',
        'last_contacted_at' => 'datetime',
    'ac_updated_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // Sales relations
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesOrderItems(): HasManyThrough
    {
        return $this->hasManyThrough(SalesOrderItem::class, SalesOrder::class, 'contact_id', 'sales_order_id');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ContactCustomField::class);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(ContactIdentity::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Auto-normalize key identity fields on save
    protected static function booted(): void
    {
        static::saving(function (Contact $model) {
            // normalized_email
            $normEmail = null;
            if ($model->email) {
                $normEmail = mb_strtolower(trim((string) $model->email));
                if (str_contains($normEmail, '@placeholder.local') || str_starts_with($normEmail, 'noemail-')) {
                    $normEmail = null;
                }
            }
            $model->normalized_email = $normEmail;

            // normalized_phone (basic normalizer with Czech defaults)
            $p = $model->phone ? preg_replace('/[^0-9+]/', '', (string) $model->phone) : null;
            if ($p && !str_starts_with($p, '+')) {
                $digits = preg_replace('/\D/', '', $p);
                if ($digits && preg_match('/^420?\d{9}$/', $digits)) {
                    if (strlen($digits) === 12 && str_starts_with($digits, '420')) { $p = '+'.$digits; }
                    elseif (strlen($digits) === 9) { $p = '+420'.$digits; }
                }
            }
            $model->normalized_phone = $p ?: null;
        });
    }
}
