<?php

namespace Tests\Feature\Api;

use App\Mail\RideConfirmation;
use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicRideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

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

    private function rideWithToken(Event $event, string $token = 'mytoken'): Ride
    {
        return Ride::factory()->create([
            'event_id'   => $event->id,
            'edit_token' => $token,
        ]);
    }

    private function auth(): array
    {
        $user  = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;
        return [$user, ['Authorization' => "Bearer $token"]];
    }

    // -------------------------------------------------------------------------
    // POST /api/e/{slug}/rides
    // -------------------------------------------------------------------------

    public function test_anyone_can_create_a_ride_without_auth(): void
    {
        $event = Event::factory()->create();

        $this->postJson("/api/e/{$event->slug}/rides", $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['id', 'edit_token', 'type', 'direction', 'seats', 'name', 'email', 'location']);
    }

    public function test_create_returns_edit_token(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/e/{$event->slug}/rides", $this->payload())
            ->assertCreated();

        $this->assertNotEmpty($response->json('edit_token'));
        $this->assertSame(64, strlen($response->json('edit_token')));
    }

    public function test_create_stores_ride_and_departure_location(): void
    {
        $event = Event::factory()->create();

        $this->postJson("/api/e/{$event->slug}/rides", $this->payload())->assertCreated();

        $this->assertDatabaseHas('rides', ['name' => 'Max Mustermann', 'email' => 'max@example.com']);
        $this->assertDatabaseHas('locations', ['address' => 'Teststraße 1, 10115 Berlin']);
    }

    public function test_create_returns_404_for_unknown_slug(): void
    {
        $this->postJson('/api/e/doesnotexist/rides', $this->payload())->assertNotFound();
    }

    public function test_create_requires_type(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['type' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    public function test_create_rejects_invalid_type(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['type' => 'beg']))
            ->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    public function test_create_requires_direction(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['direction' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors(['direction']);
    }

    public function test_create_requires_outbound_at_for_outbound_direction(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload([
            'direction'   => 'outbound-only',
            'outbound_at' => null,
            'return_at'   => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['outbound_at']);
    }

    public function test_create_requires_return_at_for_return_direction(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload([
            'direction'   => 'return-only',
            'outbound_at' => null,
            'return_at'   => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['return_at']);
    }

    public function test_create_requires_both_dates_for_both_ways(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload([
            'direction'   => 'both-ways',
            'outbound_at' => null,
            'return_at'   => null,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['outbound_at', 'return_at']);
    }

    public function test_create_allows_null_outbound_for_return_only(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload([
            'direction'   => 'return-only',
            'outbound_at' => null,
            'return_at'   => '2025-09-03T18:00',
        ]))->assertCreated();
    }

    public function test_create_requires_name(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['name' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_create_requires_valid_email(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['email' => 'notanemail']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_create_requires_at_least_one_contact_method(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['contact_methods' => []]))
            ->assertUnprocessable()->assertJsonValidationErrors(['contact_methods']);
    }

    public function test_create_rejects_invalid_contact_method(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['contact_methods' => ['fax']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['contact_methods.0']);
    }

    public function test_create_requires_seats_between_1_and_8(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['seats' => 0]))
            ->assertUnprocessable()->assertJsonValidationErrors(['seats']);
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['seats' => 9]))
            ->assertUnprocessable()->assertJsonValidationErrors(['seats']);
    }

    public function test_create_requires_location(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['location' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors(['location']);
    }

    public function test_create_validates_latitude_range(): void
    {
        $event = Event::factory()->create();
        $loc = array_merge($this->validLocation, ['latitude' => 91]);
        $this->postJson("/api/e/{$event->slug}/rides", $this->payload(['location' => $loc]))
            ->assertUnprocessable()->assertJsonValidationErrors(['location.latitude']);
    }

    public function test_create_sends_confirmation_email(): void
    {
        Mail::fake();

        $event = Event::factory()->create(['name' => 'Testkongress 2025']);

        $this->postJson("/api/e/{$event->slug}/rides", $this->payload([
            'email' => 'max@example.com',
            'name'  => 'Max Mustermann',
        ]))->assertCreated();

        Mail::assertSent(RideConfirmation::class, function (RideConfirmation $mail) use ($event) {
            return $mail->hasTo('max@example.com')
                && str_contains($mail->envelope()->subject, 'Testkongress 2025')
                && str_contains($mail->confirmUrl, '/confirm?token=')
                && str_contains($mail->editUrl, '/edit?token=')
                && str_contains($mail->deleteUrl, '/delete?token=');
        });
    }

    public function test_guest_created_ride_is_unconfirmed(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/e/{$event->slug}/rides", $this->payload())->assertCreated();

        $this->assertNull($response->json('confirmed_at'));
        $this->assertDatabaseHas('rides', ['id' => $response->json('id'), 'confirmed_at' => null]);
    }

    public function test_ride_created_while_logged_in_is_confirmed_immediately(): void
    {
        [$user, $headers] = $this->auth();
        $event = Event::factory()->create();

        $response = $this->withHeaders($headers)
            ->postJson("/api/e/{$event->slug}/rides", $this->payload())
            ->assertCreated();

        $this->assertNotNull($response->json('confirmed_at'));
        $this->assertDatabaseHas('rides', ['id' => $response->json('id'), 'user_id' => $user->id]);
    }

    // -------------------------------------------------------------------------
    // POST /api/e/{slug}/rides/{id}/confirm
    // -------------------------------------------------------------------------

    public function test_confirm_with_correct_token_sets_confirmed_at(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'confirm_token');
        $ride->update(['confirmed_at' => null]);

        $response = $this->postJson("/api/e/{$event->slug}/rides/{$ride->id}/confirm", [
            'edit_token' => 'confirm_token',
        ])->assertOk();

        $this->assertNotNull($response->json('confirmed_at'));
        $this->assertNotNull($ride->fresh()->confirmed_at);
    }

    public function test_confirm_with_wrong_token_returns_403(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'real_token');
        $ride->update(['confirmed_at' => null]);

        $this->postJson("/api/e/{$event->slug}/rides/{$ride->id}/confirm", [
            'edit_token' => 'wrong_token',
        ])->assertForbidden();

        $this->assertNull($ride->fresh()->confirmed_at);
    }

    public function test_confirm_with_wrong_slug_returns_404(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'tok');
        $ride->update(['confirmed_at' => null]);

        $this->postJson("/api/e/wrongslug/rides/{$ride->id}/confirm", [
            'edit_token' => 'tok',
        ])->assertNotFound();
    }

    public function test_confirm_is_idempotent_for_already_confirmed_ride(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'tok');
        $confirmedAt = $ride->confirmed_at;

        $this->postJson("/api/e/{$event->slug}/rides/{$ride->id}/confirm", [
            'edit_token' => 'tok',
        ])->assertOk();

        $this->assertEquals($confirmedAt, $ride->fresh()->confirmed_at);
    }

    // -------------------------------------------------------------------------
    // PUT /api/e/{slug}/rides/{id}
    // -------------------------------------------------------------------------

    public function test_update_with_correct_token_succeeds(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'valid_token_abc');

        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", $this->payload([
            'edit_token' => 'valid_token_abc',
            'name'       => 'Neuer Name',
        ]))->assertOk()->assertJsonPath('name', 'Neuer Name');
    }

    public function test_update_persists_changes_to_database(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'tok');

        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", $this->payload([
            'edit_token' => 'tok',
            'seats'      => 4,
        ]))->assertOk();

        $this->assertDatabaseHas('rides', ['id' => $ride->id, 'seats' => 4]);
    }

    public function test_update_also_updates_departure_location(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'tok');

        $newLoc = array_merge($this->validLocation, ['address' => 'Neuer Ort, München']);
        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", $this->payload([
            'edit_token' => 'tok',
            'location'   => $newLoc,
        ]))->assertOk();

        $this->assertDatabaseHas('locations', ['address' => 'Neuer Ort, München']);
    }

    public function test_update_with_wrong_token_returns_403(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'correct_token');

        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", $this->payload([
            'edit_token' => 'wrong_token',
        ]))->assertForbidden();
    }

    public function test_update_without_token_returns_403(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event);

        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", $this->payload())
            ->assertForbidden();
    }

    public function test_update_with_wrong_slug_returns_404(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'tok');

        $this->putJson("/api/e/wrongslug/rides/{$ride->id}", $this->payload([
            'edit_token' => 'tok',
        ]))->assertNotFound();
    }

    public function test_update_validates_fields(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'tok');

        $this->putJson("/api/e/{$event->slug}/rides/{$ride->id}", $this->payload([
            'edit_token' => 'tok',
            'email'      => 'invalid',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/e/{slug}/rides/{id}
    // -------------------------------------------------------------------------

    public function test_delete_with_correct_token_removes_ride(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'del_token');

        $this->deleteJson(
            "/api/e/{$event->slug}/rides/{$ride->id}",
            ['edit_token' => 'del_token']
        )->assertNoContent();

        $this->assertDatabaseMissing('rides', ['id' => $ride->id]);
    }

    public function test_delete_with_wrong_token_returns_403_and_keeps_ride(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'real_token');

        $this->deleteJson(
            "/api/e/{$event->slug}/rides/{$ride->id}",
            ['edit_token' => 'wrong_token']
        )->assertForbidden();

        $this->assertDatabaseHas('rides', ['id' => $ride->id]);
    }

    public function test_delete_without_token_returns_403(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event);

        $this->deleteJson("/api/e/{$event->slug}/rides/{$ride->id}", [])
            ->assertForbidden();
    }

    public function test_delete_with_wrong_slug_returns_404(): void
    {
        $event = Event::factory()->create();
        $ride  = $this->rideWithToken($event, 'tok');

        $this->deleteJson(
            "/api/e/wrongslug/rides/{$ride->id}",
            ['edit_token' => 'tok']
        )->assertNotFound();
    }
}
