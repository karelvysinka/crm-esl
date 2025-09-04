<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends Model
{
    protected $fillable = [
        'name',
        'description',
        'value',
        'probability',
        'stage',
        'expected_close_date',
        'contact_id',
        'company_id',
        'assigned_to',
        'created_by',
        'closed_at',
        'close_reason',
        'close_notes'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Stage scopes
    public function scopeOpen($query)
    {
        return $query->whereNotIn('stage', ['won', 'lost']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('stage', ['won', 'lost']);
    }

    public function scopeWon($query)
    {
        return $query->where('stage', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('stage', 'lost');
    }

    // Stage helpers
    public function getStageColorAttribute()
    {
        return match($this->stage) {
            'prospecting' => 'secondary',
            'qualification' => 'info',
            'proposal' => 'warning',
            'negotiation' => 'primary',
            'closing' => 'warning',
            'won' => 'success',
            'lost' => 'danger',
            default => 'secondary'
        };
    }

    public function getStageLabelAttribute()
    {
        return match($this->stage) {
            'prospecting' => 'Prospektování',
            'qualification' => 'Kvalifikace',
            'proposal' => 'Návrh',
            'negotiation' => 'Vyjednávání',
            'closing' => 'Uzavírání',
            'won' => 'Vyhráno',
            'lost' => 'Prohráno',
            default => ucfirst($this->stage)
        };
    }
}
