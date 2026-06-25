<?php

namespace App\Models;

class LocationObserver
{
    public function updated(Location $location): void
    {
        if (! $location->wasChanged(['latitude', 'longitude'])) {
            return;
        }

        LocationRoute::where('from_location_id', $location->id)
            ->orWhere('to_location_id', $location->id)
            ->delete();
    }
}
