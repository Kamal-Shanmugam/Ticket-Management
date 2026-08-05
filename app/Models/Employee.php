<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Employee extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'role_id',
        'department_id',
        'name',
        'email',
        'password',
        'is_available',
        'last_assigned_at',
        'api_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_available' => 'boolean',
            'last_assigned_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'employee_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(TicketComment::class, 'commenter');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(TicketLog::class, 'performed_by');
    }

    // Helper functions for role checking
    public function isAdmin(): bool
    {
        return $this->role && $this->role->slug === 'admin';
    }

    public function isTeamLead(): bool
    {
        return $this->role && $this->role->slug === 'team_lead';
    }

    public function isStaff(): bool
    {
        return $this->role && $this->role->slug === 'staff';
    }
}
