<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    private array $validLocation = [
        'address'      => 'Musterstraße 1, 10115 Berlin',
        'latitude'     => 52.5200,
        'longitude'    => 13.4050,
        'country_code' => 'DE',
    ];

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'     => 'Mein Seminar',
            'start_at' => '2025-09-01T10:00',
            'end_at'   => '2025-09-03T18:00',
            'location' => $this->validLocation,
        ], $overrides);
    }

    private function auth(array $attributes = []): array
    {
        $user  = User::factory()->create($attributes);
        $token = $user->createToken('spa')->plainTextToken;
        return [$user, ['Authorization' => "Bearer $token"]];
    }

    // -------------------------------------------------------------------------
    // GET /api/settings
    // -------------------------------------------------------------------------

    public function test_settings_endpoint_is_public(): void
    {
        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonStructure(['map_center_lat', 'map_center_lng', 'map_zoom']);
    }

    public function test_settings_returns_default_values(): void
    {
        $response = $this->getJson('/api/settings');

        $this->assertEqualsWithDelta(50.9333, $response->json('map_center_lat'), 0.0001);
        $this->assertEqualsWithDelta(10.5511, $response->json('map_center_lng'), 0.0001);
        $this->assertSame(6, $response->json('map_zoom'));
    }

    // -------------------------------------------------------------------------
    // POST /api/events
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_create_event(): void
    {
        [, $headers] = $this->auth();

        $this->postJson('/api/events', $this->payload(), $headers)
            ->assertCreated()
            ->assertJsonPath('name', 'Mein Seminar')
            ->assertJsonStructure(['id', 'slug', 'location' => ['address', 'latitude', 'longitude']]);
    }

    public function test_create_stores_event_and_location_in_database(): void
    {
        [, $headers] = $this->auth();

        $this->postJson('/api/events', $this->payload(), $headers)->assertCreated();

        $this->assertDatabaseHas('events', ['name' => 'Mein Seminar']);
        $this->assertDatabaseHas('locations', ['address' => 'Musterstraße 1, 10115 Berlin']);
    }

    public function test_create_assigns_slug_automatically(): void
    {
        [, $headers] = $this->auth();

        $response = $this->postJson('/api/events', $this->payload(), $headers);

        $slug = $response->json('slug');
        $this->assertNotEmpty($slug);
        $this->assertSame(16, strlen($slug));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $slug);
    }

    public function test_create_sets_creator_to_authenticated_user(): void
    {
        [$user, $headers] = $this->auth();

        $response = $this->postJson('/api/events', $this->payload(), $headers)->assertCreated();

        $this->assertDatabaseHas('events', [
            'id'            => $response->json('id'),
            'created_by_id' => $user->id,
        ]);
    }

    public function test_create_requires_authentication(): void
    {
        $this->postJson('/api/events', $this->payload())->assertUnauthorized();
    }

    public function test_create_requires_name(): void
    {
        [, $headers] = $this->auth();
        $this->postJson('/api/events', $this->payload(['name' => '']), $headers)
            ->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_create_requires_start_at(): void
    {
        [, $headers] = $this->auth();
        $this->postJson('/api/events', $this->payload(['start_at' => null]), $headers)
            ->assertUnprocessable()->assertJsonValidationErrors(['start_at']);
    }

    public function test_create_requires_end_after_start(): void
    {
        [, $headers] = $this->auth();
        $this->postJson('/api/events', $this->payload([
            'start_at' => '2025-09-03T18:00',
            'end_at'   => '2025-09-01T10:00',
        ]), $headers)->assertUnprocessable()->assertJsonValidationErrors(['end_at']);
    }

    public function test_create_rejects_end_equal_to_start(): void
    {
        [, $headers] = $this->auth();
        $this->postJson('/api/events', $this->payload([
            'start_at' => '2025-09-01T10:00',
            'end_at'   => '2025-09-01T10:00',
        ]), $headers)->assertUnprocessable()->assertJsonValidationErrors(['end_at']);
    }

    public function test_create_requires_location(): void
    {
        [, $headers] = $this->auth();
        $this->postJson('/api/events', $this->payload(['location' => null]), $headers)
            ->assertUnprocessable()->assertJsonValidationErrors(['location']);
    }

    public function test_create_requires_location_address(): void
    {
        [, $headers] = $this->auth();
        $loc = array_merge($this->validLocation, ['address' => '']);
        $this->postJson('/api/events', $this->payload(['location' => $loc]), $headers)
            ->assertUnprocessable()->assertJsonValidationErrors(['location.address']);
    }

    public function test_create_validates_latitude_range(): void
    {
        [, $headers] = $this->auth();
        $loc = array_merge($this->validLocation, ['latitude' => 91]);
        $this->postJson('/api/events', $this->payload(['location' => $loc]), $headers)
            ->assertUnprocessable()->assertJsonValidationErrors(['location.latitude']);
    }

    public function test_create_validates_longitude_range(): void
    {
        [, $headers] = $this->auth();
        $loc = array_merge($this->validLocation, ['longitude' => -181]);
        $this->postJson('/api/events', $this->payload(['location' => $loc]), $headers)
            ->assertUnprocessable()->assertJsonValidationErrors(['location.longitude']);
    }

    // -------------------------------------------------------------------------
    // GET /api/events/{id}
    // -------------------------------------------------------------------------

    public function test_owner_can_fetch_their_event(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);

        $this->getJson("/api/events/{$event->id}", $headers)
            ->assertOk()
            ->assertJsonPath('id', $event->id)
            ->assertJsonStructure(['location', 'created_by', 'rides']);
    }

    public function test_admin_can_fetch_any_event(): void
    {
        [, $headers] = $this->auth(['is_admin' => true]);
        $event = Event::factory()->create();

        $this->getJson("/api/events/{$event->id}", $headers)->assertOk();
    }

    public function test_user_cannot_fetch_other_users_event(): void
    {
        [, $headers] = $this->auth();
        $event = Event::factory()->create();

        $this->getJson("/api/events/{$event->id}", $headers)->assertForbidden();
    }

    public function test_fetch_nonexistent_event_returns_404(): void
    {
        [, $headers] = $this->auth();
        $this->getJson('/api/events/99999', $headers)->assertNotFound();
    }

    public function test_show_includes_ride_count_via_rides_relation(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        Ride::factory()->count(2)->create(['event_id' => $event->id]);

        $response = $this->getJson("/api/events/{$event->id}", $headers);

        $this->assertCount(2, $response->json('rides'));
    }

    public function test_show_rides_do_not_contain_edit_tokens(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        Ride::factory()->create(['event_id' => $event->id, 'edit_token' => 'secret_token']);

        $response = $this->getJson("/api/events/{$event->id}", $headers);

        $this->assertNull($response->json('rides.0.edit_token'));
        $this->assertArrayNotHasKey('edit_token', $response->json('rides.0'));
    }

    // -------------------------------------------------------------------------
    // PUT /api/events/{id}
    // -------------------------------------------------------------------------

    public function test_owner_can_update_their_event(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);

        $this->putJson("/api/events/{$event->id}", $this->payload(['name' => 'Geändertes Event']), $headers)
            ->assertOk()
            ->assertJsonPath('name', 'Geändertes Event');

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Geändertes Event']);
    }

    public function test_update_also_updates_location(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);

        $newLoc = array_merge($this->validLocation, ['address' => 'Neue Straße 99, München']);
        $this->putJson("/api/events/{$event->id}", $this->payload(['location' => $newLoc]), $headers)
            ->assertOk();

        $this->assertDatabaseHas('locations', ['address' => 'Neue Straße 99, München']);
    }

    public function test_admin_can_update_any_event(): void
    {
        [, $headers] = $this->auth(['is_admin' => true]);
        $event = Event::factory()->create();

        $this->putJson("/api/events/{$event->id}", $this->payload(['name' => 'Admin-Update']), $headers)
            ->assertOk();
    }

    public function test_user_cannot_update_other_users_event(): void
    {
        [, $headers] = $this->auth();
        $event = Event::factory()->create();

        $this->putJson("/api/events/{$event->id}", $this->payload(), $headers)->assertForbidden();
    }

    public function test_update_validates_end_after_start(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);

        $this->putJson("/api/events/{$event->id}", $this->payload([
            'start_at' => '2025-09-05T10:00',
            'end_at'   => '2025-09-01T10:00',
        ]), $headers)->assertUnprocessable()->assertJsonValidationErrors(['end_at']);
    }

    public function test_update_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $this->putJson("/api/events/{$event->id}", $this->payload())->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // DELETE /api/events/{id}
    // -------------------------------------------------------------------------

    public function test_owner_can_delete_their_event(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);

        $this->deleteJson("/api/events/{$event->id}", [], $headers)->assertNoContent();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_delete_cascades_to_rides(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        Ride::factory()->count(3)->create(['event_id' => $event->id]);

        $this->deleteJson("/api/events/{$event->id}", [], $headers)->assertNoContent();

        $this->assertDatabaseCount('rides', 0);
    }

    public function test_admin_can_delete_any_event(): void
    {
        [, $headers] = $this->auth(['is_admin' => true]);
        $event = Event::factory()->create();

        $this->deleteJson("/api/events/{$event->id}", [], $headers)->assertNoContent();
    }

    public function test_user_cannot_delete_other_users_event(): void
    {
        [, $headers] = $this->auth();
        $event = Event::factory()->create();

        $this->deleteJson("/api/events/{$event->id}", [], $headers)->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_delete_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $this->deleteJson("/api/events/{$event->id}")->assertUnauthorized();
    }
}
