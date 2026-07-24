<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Ride;

trait InteractsWithRides
{
    private function applyRideUpdate(Ride $ride, array $data): Ride
    {
        $ride->location->update($data['location']);
        $ride->update([
            'type'            => $data['type'],
            'direction'       => $data['direction'],
            'outbound_at'     => $data['outbound_at'] ?? null,
            'return_at'       => $data['return_at'] ?? null,
            'seats'           => $data['seats'],
            'name'            => $data['name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'contact_methods' => $data['contact_methods'],
            'info'            => $data['info'] ?? null,
        ]);

        return $ride->fresh()->load('location');
    }
}
