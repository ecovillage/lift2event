<?php

namespace Tests\Feature\Services;

use App\Models\Event;
use App\Models\Ride;
use App\Models\Setting;
use App\Services\RideDataRetentionCleaner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideDataRetentionCleanerTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_events_and_their_rides_past_retention_period(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);

        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        Ride::factory()->count(2)->create(['event_id' => $expiredEvent->id]);

        $result = app(RideDataRetentionCleaner::class)->run();

        $this->assertSame(['events' => 1, 'rides' => 2], $result);
        $this->assertDatabaseMissing('events', ['id' => $expiredEvent->id]);
        $this->assertDatabaseCount('rides', 0);
    }

    public function test_deletes_event_and_ride_locations(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);

        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        $ride = Ride::factory()->create(['event_id' => $expiredEvent->id]);
        $eventLocationId = $expiredEvent->location_id;
        $rideLocationId = $ride->location_id;

        app(RideDataRetentionCleaner::class)->run();

        $this->assertDatabaseMissing('locations', ['id' => $eventLocationId]);
        $this->assertDatabaseMissing('locations', ['id' => $rideLocationId]);
    }

    public function test_keeps_events_within_retention_period(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);

        $recentEvent = Event::factory()->create(['end_at' => now()->subDays(6)]);
        Ride::factory()->create(['event_id' => $recentEvent->id]);

        $result = app(RideDataRetentionCleaner::class)->run();

        $this->assertSame(['events' => 0, 'rides' => 0], $result);
        $this->assertDatabaseHas('events', ['id' => $recentEvent->id]);
    }

    public function test_dry_run_reports_but_does_not_delete(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);

        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        Ride::factory()->create(['event_id' => $expiredEvent->id]);

        $result = app(RideDataRetentionCleaner::class)->run(dryRun: true);

        $this->assertSame(['events' => 1, 'rides' => 1], $result);
        $this->assertDatabaseHas('events', ['id' => $expiredEvent->id]);
    }
}
