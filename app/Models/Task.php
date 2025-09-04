<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'priority',
    'project_id',
        'due_date',
        'completed_at',
        'assigned_to',
        'created_by',
        'taskable_type',
        'taskable_id',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Convenience relations when taskable is a customer entity
    public function company(): ?Company
    {
        return $this->taskable instanceof Company ? $this->taskable : null;
    }

    public function contact(): ?Contact
    {
        return $this->taskable instanceof Contact ? $this->taskable : null;
    }
}
