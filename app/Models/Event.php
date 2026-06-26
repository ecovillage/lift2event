<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    private const ADJECTIVES = [
        'amber', 'arctic', 'autumn', 'azure', 'bold', 'brave', 'bright', 'calm',
        'clear', 'clever', 'coastal', 'cool', 'cosmic', 'crisp', 'crystal', 'daring',
        'dawn', 'deep', 'eager', 'early', 'easy', 'electric', 'emerald', 'endless',
        'epic', 'fair', 'fast', 'fierce', 'fleet', 'fresh', 'frosty', 'gentle',
        'glad', 'glowing', 'golden', 'grand', 'great', 'green', 'happy', 'hardy',
        'hidden', 'high', 'honest', 'jolly', 'keen', 'kind', 'lively', 'lone',
        'lucky', 'lunar', 'lush', 'mighty', 'misty', 'mellow', 'noble', 'north',
        'open', 'outer', 'patient', 'peaceful', 'polar', 'proud', 'pure', 'quick',
        'quiet', 'radiant', 'rapid', 'rare', 'rising', 'roaming', 'rocky', 'royal',
        'rustic', 'serene', 'shining', 'silver', 'sleek', 'smooth', 'solar', 'soaring',
        'still', 'stormy', 'strong', 'sunny', 'swift', 'tall', 'tidal', 'tranquil',
        'urban', 'vast', 'vibrant', 'vivid', 'warm', 'wild', 'windy', 'wise',
        'young', 'zesty',
    ];

    private const NOUNS = [
        'anchor', 'apple', 'arrow', 'atlas', 'bay', 'beacon', 'birch', 'brook',
        'canyon', 'cedar', 'cliff', 'cloud', 'comet', 'cove', 'crane', 'creek',
        'dune', 'eagle', 'echo', 'elm', 'falcon', 'fern', 'field', 'fjord',
        'flame', 'flint', 'flower', 'foam', 'forest', 'fox', 'frost', 'gale',
        'glade', 'glacier', 'grove', 'gulf', 'harbor', 'hawk', 'haven', 'heath',
        'hill', 'horizon', 'island', 'ivy', 'lake', 'lark', 'leaf', 'light',
        'maple', 'marsh', 'meadow', 'mesa', 'mist', 'moon', 'moss', 'mountain',
        'oak', 'ocean', 'orbit', 'otter', 'pass', 'peak', 'pine', 'plain',
        'pond', 'prism', 'rain', 'raven', 'reef', 'ridge', 'river', 'road',
        'robin', 'rock', 'rose', 'route', 'sage', 'sand', 'sea', 'shore',
        'sky', 'snow', 'spark', 'spring', 'star', 'stone', 'stream', 'sun',
        'tide', 'trail', 'tree', 'vale', 'valley', 'vine', 'wave', 'willow',
        'wind', 'wolf',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                do {
                    $adjective = self::ADJECTIVES[array_rand(self::ADJECTIVES)];
                    $noun = self::NOUNS[array_rand(self::NOUNS)];
                    $slug = $adjective . '-' . $noun;
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
