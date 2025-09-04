<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    protected $fillable = [
        'opportunity_id',
        'name',
        'amount',
        'close_date',
        'status',
        'terms',
        'notes',
        'signed_by_contact_id',
        'signed_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'close_date' => 'date',
        'signed_at' => 'datetime',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function signedByContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'signed_by_contact_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
