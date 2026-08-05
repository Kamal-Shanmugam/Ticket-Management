<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'customer_id',
        'department_id',
        'ticket_priority_id',
        'ticket_status_id',
        'assigned_to',
        'title',
        'description',
        'estimated_resolution_at',
        'actual_resolution_at',
        'sla_warning_notified',
        'sla_breached_notified',
    ];

    protected function casts(): array
    {
        return [
            'estimated_resolution_at' => 'datetime',
            'actual_resolution_at' => 'datetime',
            'sla_warning_notified' => 'boolean',
            'sla_breached_notified' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    // Helper functions
    public function isResolved(): bool
    {
        return $this->status && $this->status->slug === 'resolved';
    }

    public function isClosed(): bool
    {
        return $this->status && $this->status->slug === 'closed';
    }

    public function isSlaBreached(): bool
    {
        if ($this->isResolved() || $this->isClosed()) {
            return $this->actual_resolution_at && $this->estimated_resolution_at && $this->actual_resolution_at->gt($this->estimated_resolution_at);
        }
        return $this->estimated_resolution_at && now()->gt($this->estimated_resolution_at);
    }
}
