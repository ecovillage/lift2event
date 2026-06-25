<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminRideTest extends TestCase
{
    use RefreshDatabase;

    private array $validLocation = [
        'address'      => 'Teststraße 1, 10115 Berlin',
        'latitude'     => 52.5200,
        'longitude'    => 13.4050,
        'country_code' => 'DE',
    ];

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type'            => 'offer',
            'direction'       => 'both-ways',
            'outbound_at'     => '2025-09-01T10:00',
            'return_at'       => '2025-09-03T18:00',
            'seats'           => 2,
            'name'            => 'Max Mustermann',
            'email'           => 'max@example.com',
            'phone'           => null,
            'contact_methods' => ['email'],
            'info'            => null,
            'location'        => $this->validLocation,
        ], $overrides);
    }

    private function auth(array $attributes = []): array
    {
        $user  = User::factory()->create($attributes);
        $token = $user->createToken('spa')->plainTextToken;
        return [$user, ['Authorization' => "Bearer $token"]];
    }

    // -------------------------------------------------------------------------
    // PUT /api/events/{event}/rides/{ride}
    // -------------------------------------------------------------------------

    public function test_creator_can_update_ride_without_token(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        $ride  = Ride::factory()->create(['event_id' => $event->id, 'edit_token' => 'sometoken']);

        $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload([
            'name' => 'Geänderter Name',
        ]), $headers)->assertOk()->assertJsonPath('name', 'Geänderter Name');

        $this->assertDatabaseHas('rides', ['id' => $ride->id, 'name' => 'Geänderter Name']);
    }

    public function test_admin_can_update_any_ride_without_token(): void
    {
        [, $headers] = $this->auth(['is_admin' => true]);
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload([
            'name' => 'Admin-Update',
        ]), $headers)->assertOk()->assertJsonPath('name', 'Admin-Update');
    }

    public function test_update_response_does_not_contain_edit_token(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        $ride  = Ride::factory()->create(['event_id' => $event->id, 'edit_token' => 'secret']);

        $response = $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload(), $headers)
            ->assertOk();

        $this->assertArrayNotHasKey('edit_token', $response->json());
    }

    public function test_update_also_updates_departure_location(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $newLoc = array_merge($this->validLocation, ['address' => 'Neuer Ort, München']);
        $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload([
            'location' => $newLoc,
        ]), $headers)->assertOk();

        $this->assertDatabaseHas('locations', ['address' => 'Neuer Ort, München']);
    }

    public function test_non_creator_non_admin_cannot_update_ride(): void
    {
        [, $headers] = $this->auth();
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload(), $headers)
            ->assertForbidden();
    }

    public function test_update_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload())
            ->assertUnauthorized();
    }

    public function test_update_returns_404_when_ride_belongs_to_other_event(): void
    {
        [$user, $headers] = $this->auth();
        $event      = Event::factory()->create(['created_by_id' => $user->id]);
        $otherEvent = Event::factory()->create(['created_by_id' => $user->id]);
        $ride       = Ride::factory()->create(['event_id' => $otherEvent->id]);

        $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload(), $headers)
            ->assertNotFound();
    }

    public function test_update_validates_fields(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->putJson("/api/events/{$event->id}/rides/{$ride->id}", $this->payload([
            'email' => 'invalid',
        ]), $headers)->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/events/{event}/rides/{ride}
    // -------------------------------------------------------------------------

    public function test_creator_can_delete_ride_without_token(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        $ride  = Ride::factory()->create(['event_id' => $event->id, 'edit_token' => 'sometoken']);

        $this->deleteJson("/api/events/{$event->id}/rides/{$ride->id}", [], $headers)
            ->assertNoContent();

        $this->assertDatabaseMissing('rides', ['id' => $ride->id]);
    }

    public function test_admin_can_delete_any_ride_without_token(): void
    {
        [, $headers] = $this->auth(['is_admin' => true]);
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->deleteJson("/api/events/{$event->id}/rides/{$ride->id}", [], $headers)
            ->assertNoContent();
    }

    public function test_non_creator_non_admin_cannot_delete_ride(): void
    {
        [, $headers] = $this->auth();
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->deleteJson("/api/events/{$event->id}/rides/{$ride->id}", [], $headers)
            ->assertForbidden();

        $this->assertDatabaseHas('rides', ['id' => $ride->id]);
    }

    public function test_delete_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->deleteJson("/api/events/{$event->id}/rides/{$ride->id}")
            ->assertUnauthorized();
    }

    public function test_delete_returns_404_when_ride_belongs_to_other_event(): void
    {
        [$user, $headers] = $this->auth();
        $event      = Event::factory()->create(['created_by_id' => $user->id]);
        $otherEvent = Event::factory()->create(['created_by_id' => $user->id]);
        $ride       = Ride::factory()->create(['event_id' => $otherEvent->id]);

        $this->deleteJson("/api/events/{$event->id}/rides/{$ride->id}", [], $headers)
            ->assertNotFound();

        $this->assertDatabaseHas('rides', ['id' => $ride->id]);
    }

    // -------------------------------------------------------------------------
    // GET /api/events/{event}/rides/{ride}/route
    // -------------------------------------------------------------------------

    public function test_creator_can_fetch_route(): void
    {
        Http::fake(['*' => Http::response([
            'features' => [['geometry' => ['coordinates' => [[13.4, 52.5], [11.6, 48.1]]]]],
        ])]);

        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $response = $this->getJson("/api/events/{$event->id}/rides/{$ride->id}/route", $headers)->assertOk();

        $this->assertSame([[13.4, 52.5], [11.6, 48.1]], $response->json('geometry'));
    }

    public function test_admin_can_fetch_route_for_any_event(): void
    {
        Http::fake(['*' => Http::response([
            'features' => [['geometry' => ['coordinates' => [[1, 1]]]]],
        ])]);

        [, $headers] = $this->auth(['is_admin' => true]);
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->getJson("/api/events/{$event->id}/rides/{$ride->id}/route", $headers)->assertOk();
    }

    public function test_non_creator_non_admin_cannot_fetch_route(): void
    {
        [, $headers] = $this->auth();
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->getJson("/api/events/{$event->id}/rides/{$ride->id}/route", $headers)
            ->assertForbidden();
    }

    public function test_route_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id]);

        $this->getJson("/api/events/{$event->id}/rides/{$ride->id}/route")
            ->assertUnauthorized();
    }
}
