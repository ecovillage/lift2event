<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventListTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(array $attributes = []): array
    {
        $user  = User::factory()->create($attributes);
        $token = $user->createToken('spa')->plainTextToken;
        return [$user, ['Authorization' => "Bearer $token"]];
    }

    // -------------------------------------------------------------------------
    // GET /api/events
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/events')->assertUnauthorized();
    }

    public function test_regular_user_sees_only_own_events(): void
    {
        [$user, $headers] = $this->actingAsUser();
        $other            = User::factory()->create();

        Event::factory()->create(['created_by_id' => $user->id, 'name' => 'Mein Event']);
        Event::factory()->create(['created_by_id' => $other->id, 'name' => 'Fremdes Event']);

        $response = $this->getJson('/api/events', $headers)->assertOk();

        $names = collect($response->json())->pluck('name');
        $this->assertContains('Mein Event', $names);
        $this->assertNotContains('Fremdes Event', $names);
    }

    public function test_admin_sees_all_events_from_all_users(): void
    {
        [$admin, $headers] = $this->actingAsUser(['is_admin' => true]);
        $other             = User::factory()->create();

        Event::factory()->create(['created_by_id' => $admin->id, 'name' => 'Admin Event']);
        Event::factory()->create(['created_by_id' => $other->id, 'name' => 'Nutzer Event']);

        $response = $this->getJson('/api/events', $headers)->assertOk();

        $names = collect($response->json())->pluck('name');
        $this->assertContains('Admin Event', $names);
        $this->assertContains('Nutzer Event', $names);
        $this->assertCount(2, $response->json());
    }

    public function test_events_are_sorted_by_start_date_descending(): void
    {
        [$admin, $headers] = $this->actingAsUser(['is_admin' => true]);

        Event::factory()->create(['start_at' => '2025-01-01 10:00', 'name' => 'Ältestes']);
        Event::factory()->create(['start_at' => '2025-06-01 10:00', 'name' => 'Mittleres']);
        Event::factory()->create(['start_at' => '2025-12-01 10:00', 'name' => 'Neuestes']);

        $names = collect($this->getJson('/api/events', $headers)->json())->pluck('name')->all();

        $this->assertSame(['Neuestes', 'Mittleres', 'Ältestes'], $names);
    }

    public function test_response_includes_location(): void
    {
        [$user, $headers] = $this->actingAsUser();
        $location         = Location::factory()->create(['address' => 'Musterstraße 1, Berlin']);

        Event::factory()->create(['created_by_id' => $user->id, 'location_id' => $location->id]);

        $event = $this->getJson('/api/events', $headers)->json(0);

        $this->assertArrayHasKey('location', $event);
        $this->assertSame('Musterstraße 1, Berlin', $event['location']['address']);
    }

    public function test_response_includes_ride_count(): void
    {
        [$user, $headers] = $this->actingAsUser();
        $event            = Event::factory()->create(['created_by_id' => $user->id]);

        Ride::factory()->count(3)->create(['event_id' => $event->id]);

        $result = $this->getJson('/api/events', $headers)->json(0);

        $this->assertArrayHasKey('rides_count', $result);
        $this->assertSame(3, $result['rides_count']);
    }

    public function test_ride_count_is_zero_for_event_without_rides(): void
    {
        [$user, $headers] = $this->actingAsUser();
        Event::factory()->create(['created_by_id' => $user->id]);

        $result = $this->getJson('/api/events', $headers)->json(0);

        $this->assertSame(0, $result['rides_count']);
    }

    public function test_user_with_no_events_receives_empty_list(): void
    {
        [$user, $headers] = $this->actingAsUser();

        $this->getJson('/api/events', $headers)
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_response_includes_creator_info(): void
    {
        [$user, $headers] = $this->actingAsUser();
        Event::factory()->create(['created_by_id' => $user->id]);

        $event = $this->getJson('/api/events', $headers)->json(0);

        $this->assertArrayHasKey('created_by', $event);
        $this->assertSame($user->id, $event['created_by']['id']);
    }

    public function test_admin_event_count_does_not_bleed_between_users(): void
    {
        // Two separate users — each should only see their own events
        [$userA, $headersA] = $this->actingAsUser();
        [$userB, $headersB] = $this->actingAsUser();

        Event::factory()->count(2)->create(['created_by_id' => $userA->id]);
        Event::factory()->count(3)->create(['created_by_id' => $userB->id]);

        $this->assertCount(2, $this->getJson('/api/events', $headersA)->json());

        $this->flushAuthGuards();

        $this->assertCount(3, $this->getJson('/api/events', $headersB)->json());
    }
}
