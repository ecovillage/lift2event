<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function manage(User $user, Event $event): bool
    {
        return $user->is_admin || $event->created_by_id === $user->id;
    }
}
