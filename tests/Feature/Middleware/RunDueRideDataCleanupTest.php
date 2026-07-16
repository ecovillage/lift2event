<?php

namespace Tests\Feature\Middleware;

use App\Models\Event;
use App\Models\Setting;
use App\Services\RideDataRetentionCleaner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RunDueRideDataCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_request_of_the_day_triggers_cleanup(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        $otherEvent   = Event::factory()->create();

        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();

        $this->assertDatabaseMissing('events', ['id' => $expiredEvent->id]);
        $this->assertNotNull(Setting::instance()->rides_cleanup_last_run_at);
    }

    public function test_requesting_the_just_expired_event_itself_returns_404(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);

        // The cleanup runs before the controller resolves the event, so this
        // must behave exactly like requesting an unknown slug.
        $this->getJson("/api/e/{$expiredEvent->slug}")->assertNotFound();
    }

    public function test_cleanup_does_not_run_twice_on_the_same_day(): void
    {
        Setting::instance()->update([
            'ride_data_retention_days'    => 7,
            'rides_cleanup_last_run_at'   => now(),
        ]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        $otherEvent   = Event::factory()->create();

        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();

        $this->assertDatabaseHas('events', ['id' => $expiredEvent->id]);
    }

    public function test_cleanup_runs_again_on_a_new_day(): void
    {
        Setting::instance()->update([
            'ride_data_retention_days'  => 7,
            'rides_cleanup_last_run_at' => now()->subDay()->startOfDay(),
        ]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        $otherEvent   = Event::factory()->create();

        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();

        $this->assertDatabaseMissing('events', ['id' => $expiredEvent->id]);
    }

    public function test_cleanup_failure_does_not_break_the_request(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $otherEvent = Event::factory()->create();

        $this->app->bind(RideDataRetentionCleaner::class, fn () => new class extends RideDataRetentionCleaner
        {
            public function run(bool $dryRun = false): array
            {
                throw new \RuntimeException('boom');
            }
        });

        Log::shouldReceive('error')->once();

        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();
    }
}
