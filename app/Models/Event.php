<?php

namespace App\Models;

use App\Services\RideDataRetentionCleaner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    use HasFactory;

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
                $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

                do {
                    $slug = '';
                    for ($i = 0; $i < 4; $i++) {
                        $slug .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                    }
                } while (static::where('slug', $slug)->exists());

                $event->slug = $slug;
            }
        });

        // Keeps Setting::rides_cleanup_next_due_at in sync with the events
        // table, so RunDueRideDataCleanup can decide "is anything due?" from
        // a single cheap column instead of scanning events on every request.
        // Covers every way an event's retention cutoff can change: created,
        // its end_at edited, or deleted (including via deleteWithLocations()
        // in RideDataRetentionCleaner itself).
        static::saved(function (Event $event) {
            if ($event->wasRecentlyCreated || $event->wasChanged('end_at')) {
                app(RideDataRetentionCleaner::class)->rescheduleNextDue();
            }
        });

        static::deleted(function () {
            app(RideDataRetentionCleaner::class)->rescheduleNextDue();
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

    /**
     * Deletes the event along with its own location and the locations of
     * all its rides, so no orphaned Location/LocationRoute rows are left
     * behind. Ride rows are removed via DB cascade on delete.
     */
    public function deleteWithLocations(): void
    {
        DB::transaction(function () {
            $locationIds = $this->rides()->pluck('location_id')
                ->push($this->location_id)
                ->unique();

            $this->delete();

            Location::whereIn('id', $locationIds)->delete();
        });
    }
}
