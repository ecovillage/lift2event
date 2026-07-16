<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteExpiredRidesTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_expired_events(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);

        $this->artisan('app:delete-expired-rides')
            ->expectsOutputToContain('1 Veranstaltung(en)')
            ->assertSuccessful();

        $this->assertDatabaseMissing('events', ['id' => $expiredEvent->id]);
    }

    public function test_dry_run_does_not_delete(): void
    {
        Setting::instance()->update(['ride_data_retention_days' => 7]);
        $expiredEvent = Event::factory()->create(['end_at' => now()->subDays(8)]);

        $this->artisan('app:delete-expired-rides', ['--dry-run' => true])
            ->expectsOutputToContain('Würde 1 Veranstaltung(en)')
            ->assertSuccessful();

        $this->assertDatabaseHas('events', ['id' => $expiredEvent->id]);
    }
}
