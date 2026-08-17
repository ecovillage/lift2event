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

    public function test_request_triggers_cleanup_once_an_event_is_due(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        // Creating this event schedules rides_cleanup_next_due_at into the
        // past (its cutoff already lies behind us), so the next request
        // finds it due without any day-based waiting.
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        $otherEvent   = Event::factory()->create(['end_at' => now()->addDays(5)]);

        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();

        $this->assertDatabaseMissing('events', ['id' => $expiredEvent->id]);
        // Rescheduled to the now-soonest remaining event's cutoff.
        $this->assertTrue(
            Setting::instance()->rides_cleanup_next_due_at->equalTo($otherEvent->end_at->copy()->addDays(7))
        );
    }

    public function test_requesting_the_just_expired_event_itself_returns_404(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);

        // The cleanup runs before the controller resolves the event, so this
        // must behave exactly like requesting an unknown slug.
        $this->getJson("/api/e/{$expiredEvent->slug}")->assertNotFound();
    }

    public function test_cleanup_does_not_run_when_nothing_is_due_yet(): void
    {
        $notYetDueEvent = Event::factory()->create(['end_at' => now()->subDays(2)]);
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        // Force next_due_at back into the future, overriding what the
        // factory's saved-hook just computed, to isolate the guard itself.
        Setting::instance()->update(['rides_cleanup_next_due_at' => now()->addHour()]);

        $this->getJson("/api/e/{$notYetDueEvent->slug}")->assertOk();

        $this->assertDatabaseHas('events', ['id' => $notYetDueEvent->id]);
    }

    public function test_cleanup_does_not_run_again_once_the_backlog_is_cleared(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);
        $otherEvent   = Event::factory()->create(['end_at' => now()->addDays(5)]);

        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();
        $nextDueAfterFirstRun = Setting::instance()->rides_cleanup_next_due_at;

        // Nothing new became due, so a second request must leave the
        // schedule untouched instead of re-running the cleaner.
        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();

        $this->assertTrue($nextDueAfterFirstRun->equalTo(Setting::instance()->rides_cleanup_next_due_at));
    }

    public function test_next_due_at_is_null_and_cleanup_is_skipped_when_no_events_exist(): void
    {
        $this->assertNull(Setting::instance()->rides_cleanup_next_due_at);

        $this->getJson('/api/e/nichtvorhanden')->assertNotFound();

        $this->assertNull(Setting::instance()->fresh()->rides_cleanup_next_due_at);
    }

    public function test_cleanup_failure_falls_back_to_retrying_at_most_once_a_day(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $otherEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);

        $this->app->bind(RideDataRetentionCleaner::class, fn () => new class extends RideDataRetentionCleaner
        {
            public function run(bool $dryRun = false): array
            {
                throw new \RuntimeException('boom');
            }
        });

        Log::shouldReceive('error')->once();

        $this->getJson("/api/e/{$otherEvent->slug}")->assertOk();

        // The claim already pushed next_due_at a day out before the crash,
        // so a broken cleaner degrades to at most one retry per day instead
        // of re-running (and re-failing) on every single request.
        $this->assertTrue(
            Setting::instance()->rides_cleanup_next_due_at->between(now()->addHours(23), now()->addDays(1)->addMinute())
        );
    }
}
