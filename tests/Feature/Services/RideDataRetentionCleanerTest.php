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

    public function test_run_reschedules_next_due_at_to_the_soonest_remaining_event(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);

        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        $remainingEvent = Event::factory()->create(['end_at' => now()->addDays(3)]);

        app(RideDataRetentionCleaner::class)->run();

        $this->assertTrue(
            Setting::instance()->fresh()->rides_cleanup_next_due_at
                ->equalTo($remainingEvent->end_at->copy()->addDays(7))
        );
    }

    public function test_run_sets_next_due_at_to_null_when_no_events_remain(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        Event::factory()->create(['end_at' => now()->subDays(8)]);

        app(RideDataRetentionCleaner::class)->run();

        $this->assertNull(Setting::instance()->fresh()->rides_cleanup_next_due_at);
    }

    public function test_dry_run_does_not_reschedule_next_due_at(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        Event::factory()->create(['end_at' => now()->subDays(8)]);
        // Overwrite whatever the creation's saved-hook just computed, so we
        // can isolate that run(dryRun: true) itself leaves it alone.
        Setting::instance()->update(['rides_cleanup_next_due_at' => now()->addDay()]);
        $sentinel = Setting::instance()->rides_cleanup_next_due_at;

        app(RideDataRetentionCleaner::class)->run(dryRun: true);

        $this->assertTrue($sentinel->equalTo(Setting::instance()->fresh()->rides_cleanup_next_due_at));
    }
}
