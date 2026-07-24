<?php

namespace App\Models;

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
                $adjectives = require base_path('app/Data/adjectives.php');
                $nouns = require base_path('app/Data/nouns.php');

                do {
                    $slug = $adjectives[array_rand($adjectives)] . '-' . $nouns[array_rand($nouns)];
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
