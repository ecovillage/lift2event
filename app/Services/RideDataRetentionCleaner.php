<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ride;
use App\Models\Setting;

class RideDataRetentionCleaner
{
    /**
     * Deletes events (and, via DB cascade, their rides) whose end date lies
     * further back than the configured retention period.
     *
     * @return array{events: int, rides: int}
     */
    public function run(bool $dryRun = false): array
    {
        $cutoff = now()->subDays(Setting::instance()->ride_data_retention_days);

        $rideCount = Ride::whereHas('event', fn ($query) => $query->where('end_at', '<', $cutoff))->count();
        $eventCount = 0;

        Event::where('end_at', '<', $cutoff)->chunkById(100, function ($events) use (&$eventCount, $dryRun) {
            foreach ($events as $event) {
                $eventCount++;
                if (! $dryRun) {
                    $event->delete();
                }
            }
        });

        return ['events' => $eventCount, 'rides' => $rideCount];
    }
}
