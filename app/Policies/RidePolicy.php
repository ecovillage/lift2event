<?php

namespace App\Policies;

use App\Models\Ride;
use App\Models\User;

class RidePolicy
{
    public function manage(User $user, Ride $ride): bool
    {
        return $user->is_admin || $ride->event->created_by_id === $user->id;
    }
}
