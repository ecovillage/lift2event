<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ride extends Model
{
    protected $fillable = [
        'event_id', 'user_id', 'location_id', 'type', 'direction',
        'outbound_at', 'return_at', 'seats', 'name', 'email', 'phone',
        'contact_methods', 'info', 'edit_token',
    ];

    protected function casts(): array
    {
        return [
            'outbound_at'     => 'datetime',
            'return_at'       => 'datetime',
            'contact_methods' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
