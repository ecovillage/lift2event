<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MeShaon\RequestAnalytics\Contracts\CanAccessAnalyticsDashboard;

class User extends Authenticatable implements CanAccessAnalyticsDashboard
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'is_admin', 'approved', 'preferred_language'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'approved' => 'boolean',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by_id');
    }

    public function canAccessAnalyticsDashboard(): bool
    {
        // Any logged-in user may see the analytics of all events, not just their own.
        return true;
    }
}
