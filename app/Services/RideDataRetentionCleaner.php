<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ride;
use App\Models\Setting;
use Illuminate\Support\Carbon;

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
                    $event->deleteWithLocations();
                }
            }
        });

        if (! $dryRun) {
            // Deleting events above already triggers this via Event's
            // `deleted` model event, but that only accounts for events that
            // actually got deleted. Calling it here too keeps the schedule
            // correct even when nothing was due this run (e.g. it was
            // claimed based on a since-edited event), instead of relying on
            // that side effect alone.
            $this->rescheduleNextDue();
        }

        return ['events' => $eventCount, 'rides' => $rideCount];
    }

    /**
     * Recomputes when the retention cleanup next has real work to do, based
     * on the event that will expire soonest, and caches it on the settings
     * row so RunDueRideDataCleanup can cheaply check "is anything due?" on
     * every request without scanning the events table each time.
     */
    public function rescheduleNextDue(): void
    {
        $earliestEndAt = Event::min('end_at');

        Setting::instance()->update([
            'rides_cleanup_next_due_at' => $earliestEndAt
                ? Carbon::parse($earliestEndAt)->addDays(Setting::instance()->ride_data_retention_days)
                : null,
        ]);
    }
}
