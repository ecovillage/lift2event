<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_event_page_requires_no_authentication(): void
    {
        $event = Event::factory()->create();

        $this->getJson("/api/e/{$event->slug}")->assertOk();
    }

    public function test_returns_event_and_rides_structure(): void
    {
        $event = Event::factory()->create();

        $this->getJson("/api/e/{$event->slug}")
            ->assertOk()
            ->assertJsonStructure([
                'event' => ['id', 'slug', 'name', 'start_at', 'end_at', 'location'],
                'rides',
            ]);
    }

    public function test_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/e/nonexistentslug')->assertNotFound();
    }

    public function test_rides_list_does_not_contain_edit_tokens(): void
    {
        $event = Event::factory()->create();
        Ride::factory()->create(['event_id' => $event->id, 'edit_token' => 'secret_token']);

        $response = $this->getJson("/api/e/{$event->slug}")->assertOk();

        $this->assertNull($response->json('rides.0.edit_token'));
        $this->assertArrayNotHasKey('edit_token', $response->json('rides.0'));
    }

    public function test_rides_are_sorted_offers_first_then_by_newest(): void
    {
        $event = Event::factory()->create();
        $r1 = Ride::factory()->create(['event_id' => $event->id, 'type' => 'request', 'created_at' => now()->subMinutes(5)]);
        $r2 = Ride::factory()->create(['event_id' => $event->id, 'type' => 'offer',   'created_at' => now()->subMinutes(3)]);
        $r3 = Ride::factory()->create(['event_id' => $event->id, 'type' => 'offer',   'created_at' => now()->subMinutes(1)]);

        $response = $this->getJson("/api/e/{$event->slug}")->assertOk();
        $ids = array_column($response->json('rides'), 'id');

        // offers first (newest first within type), then requests
        $this->assertSame([$r3->id, $r2->id, $r1->id], $ids);
    }

    public function test_returns_empty_rides_array_when_no_rides(): void
    {
        $event = Event::factory()->create();

        $response = $this->getJson("/api/e/{$event->slug}")->assertOk();

        $this->assertSame([], $response->json('rides'));
    }

    public function test_ride_location_is_included(): void
    {
        $event = Event::factory()->create();
        Ride::factory()->create(['event_id' => $event->id]);

        $response = $this->getJson("/api/e/{$event->slug}")->assertOk();

        $this->assertArrayHasKey('location', $response->json('rides.0'));
    }

    public function test_geocode_search_is_publicly_accessible(): void
    {
        // The geocode endpoint is now public (no auth required)
        // We can't test the Nominatim response here, but we can verify
        // the endpoint is reachable without auth and returns JSON.
        // A missing 'q' param triggers a 422 (validation), not 401.
        $this->getJson('/api/geocode/search')->assertStatus(422);
    }
}
