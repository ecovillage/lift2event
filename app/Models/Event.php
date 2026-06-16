<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['name', 'start_at', 'end_at', 'location_id', 'created_by_id', 'slug'];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                do {
                    $slug = bin2hex(random_bytes(8));
                } while (static::where('slug', $slug)->exists());

                $event->slug = $slug;
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }
}
