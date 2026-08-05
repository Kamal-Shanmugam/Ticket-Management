<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPriority extends Model
{
    protected $fillable = ['name', 'slug', 'resolution_hours'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
