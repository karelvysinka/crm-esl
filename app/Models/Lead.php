<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'source',
        'status',
        'score',
        'estimated_value',
        'notes',
        'assigned_to',
        'created_by',
        'last_activity_at',
        'converted_at',
        'converted_to_opportunity_id'
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'score' => 'integer',
        'last_activity_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    // Relationships
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedToOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'converted_to_opportunity_id');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    // Accessors
    public function getFormattedEstimatedValueAttribute(): string
    {
        if (!$this->estimated_value) {
            return '-';
        }
        return number_format($this->estimated_value, 0, ',', ' ') . ' Kč';
    }

    public function getScoreColorAttribute(): string
    {
        return match (true) {
            $this->score >= 80 => 'success',
            $this->score >= 60 => 'warning', 
            $this->score >= 40 => 'info',
            default => 'secondary'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'primary',
            'contacted' => 'info',
            'qualified' => 'warning',
            'proposal' => 'purple',
            'negotiation' => 'orange',
            'won' => 'success',
            'lost' => 'danger',
            default => 'secondary'
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'website' => 'Website',
            'referral' => 'Doporučení',
            'social_media' => 'Sociální sítě',
            'cold_call' => 'Cold call',
            'email_campaign' => 'Email kampaň',
            'trade_show' => 'Veletrh',
            'other' => 'Ostatní',
            default => $this->source
        };
    }

    public function getSourceColorAttribute(): string
    {
        return match ($this->source) {
            'website' => 'primary',
            'referral' => 'success',
            'social_media' => 'info',
            'cold_call' => 'warning',
            'email_campaign' => 'purple',
            'trade_show' => 'orange',
            'other' => 'secondary',
            default => 'secondary'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'Nový',
            'contacted' => 'Kontaktován',
            'qualified' => 'Kvalifikován',
            'proposal' => 'Nabídka',
            'negotiation' => 'Vyjednávání',
            'won' => 'Vyhrán',
            'lost' => 'Prohrán',
            default => $this->status
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['won', 'lost']);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeHighScore($query, $minScore = 70)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Methods
    public function updateScore(): void
    {
        $score = 0;
        
        // Basic scoring logic
        if ($this->phone) $score += 10;
        if ($this->estimated_value > 0) $score += 20;
        if ($this->status === 'qualified') $score += 30;
        if ($this->status === 'proposal') $score += 50;
        if ($this->status === 'negotiation') $score += 70;
        
        // Source scoring
        $score += match ($this->source) {
            'referral' => 15,
            'website' => 10,
            'social_media' => 8,
            'email_campaign' => 5,
            default => 0
        };

        $this->update(['score' => min(100, $score)]);
    }

    public function convertToOpportunity(): ?Opportunity
    {
        if ($this->status === 'won' || $this->converted_at) {
            return null;
        }

        // Create opportunity (will implement when Opportunity model is ready)
        $this->update([
            'status' => 'won',
            'converted_at' => now()
        ]);

        return null; // Will return actual opportunity when implemented
    }
}
