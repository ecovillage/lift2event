<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\LocationRoute;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationObserverTest extends TestCase
{
    use RefreshDatabase;

    private array $newLocation = [
        'address'      => 'Neuer Ort, München',
        'latitude'     => 48.1351,
        'longitude'    => 11.5820,
        'country_code' => 'DE',
    ];

    private function cacheRoute(Ride $ride, Event $event): LocationRoute
    {
        return LocationRoute::create([
            'from_location_id' => $ride->location_id,
            'to_location_id'   => $event->location_id,
            'geometry'         => [[1, 1], [2, 2]],
        ]);
    }

    private function auth(array $attributes = []): array
    {
        $user  = User::factory()->create($attributes);
        $token = $user->createToken('spa')->plainTextToken;
        return [$user, ['Authorization' => "Bearer $token"]];
    }

    public function test_ride_location_update_invalidates_cached_route(): void
    {
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id, 'edit_token' => 'tok']);
        $route = $this->cacheRoute($ride, $event);

        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", [
            'edit_token'      => 'tok',
            'type'            => $ride->type,
            'direction'       => 'both-ways',
            'outbound_at'     => '2025-09-01T10:00',
            'return_at'       => '2025-09-03T18:00',
            'seats'           => $ride->seats,
            'name'            => $ride->name,
            'email'           => $ride->email,
            'contact_methods' => ['email'],
            'location'        => $this->newLocation,
        ])->assertOk();

        $this->assertDatabaseMissing('location_routes', ['id' => $route->id]);
    }

    public function test_ride_update_without_location_change_keeps_cached_route(): void
    {
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create(['event_id' => $event->id, 'edit_token' => 'tok']);
        $route = $this->cacheRoute($ride, $event);

        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", [
            'edit_token'      => 'tok',
            'type'            => $ride->type,
            'direction'       => 'both-ways',
            'outbound_at'     => '2025-09-01T10:00',
            'return_at'       => '2025-09-03T18:00',
            'seats'           => $ride->seats,
            'name'            => 'Geänderter Name',
            'email'           => $ride->email,
            'contact_methods' => ['email'],
            'location'        => [
                'address'      => $ride->location->address,
                'latitude'     => $ride->location->latitude,
                'longitude'    => $ride->location->longitude,
                'country_code' => $ride->location->country_code,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('location_routes', ['id' => $route->id]);
    }

    public function test_event_location_update_invalidates_cached_route(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create(['created_by_id' => $user->id]);
        $ride  = Ride::factory()->create(['event_id' => $event->id]);
        $route = $this->cacheRoute($ride, $event);

        $this->putJson("/api/events/{$event->id}", [
            'name'     => $event->name,
            'start_at' => $event->start_at->toDateTimeString(),
            'end_at'   => $event->end_at->toDateTimeString(),
            'location' => $this->newLocation,
        ], $headers)->assertOk();

        $this->assertDatabaseMissing('location_routes', ['id' => $route->id]);
    }
}
