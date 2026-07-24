<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // X-Locale header (guest requests)
    // -------------------------------------------------------------------------

    public function test_defaults_to_german_without_locale_header(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Die eingegebenen Zugangsdaten sind falsch.');
    }

    public function test_x_locale_header_switches_response_to_english(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'], ['X-Locale' => 'en'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_x_locale_header_switches_response_to_french(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'], ['X-Locale' => 'fr'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Les identifiants saisis sont incorrects.');
    }

    public function test_x_locale_header_switches_response_to_chinese(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'], ['X-Locale' => 'zh'])
            ->assertUnauthorized()
            ->assertJsonPath('message', '您输入的登录信息不正确。');
    }

    public function test_unsupported_locale_header_falls_back_to_german(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'], ['X-Locale' => 'xx'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Die eingegebenen Zugangsdaten sind falsch.');
    }

    public function test_locale_does_not_leak_into_a_request_without_a_header(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'], ['X-Locale' => 'en'])
            ->assertJsonPath('message', 'The provided credentials are incorrect.');

        // A follow-up request with no header must not still be in English.
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertJsonPath('message', 'Die eingegebenen Zugangsdaten sind falsch.');
    }

    // -------------------------------------------------------------------------
    // Authenticated user's preferred_language
    // -------------------------------------------------------------------------

    public function test_authenticated_users_preferred_language_is_used(): void
    {
        $user  = User::factory()->create(['preferred_language' => 'fr']);
        $token = $user->createToken('spa')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('message', 'Déconnecté.');
    }

    public function test_authenticated_users_preferred_language_takes_precedence_over_header(): void
    {
        $user  = User::factory()->create(['preferred_language' => 'fr']);
        $token = $user->createToken('spa')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token", 'X-Locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('message', 'Déconnecté.');
    }

    // -------------------------------------------------------------------------
    // Spot checks across the affected controllers
    // -------------------------------------------------------------------------

    public function test_feedback_rate_limit_message_is_translated(): void
    {
        Notification::fake();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/feedback', ['message' => 'hi']);
        }

        $this->postJson('/api/feedback', ['message' => 'hi'], ['X-Locale' => 'en'])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests.');
    }

    public function test_user_cannot_delete_self_message_is_translated(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'preferred_language' => 'en']);
        $token = $admin->createToken('spa')->plainTextToken;

        $this->deleteJson("/api/users/{$admin->id}", [], ['Authorization' => "Bearer $token"])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete yourself.');
    }

    public function test_ride_book_offers_only_message_is_translated(): void
    {
        $event = Event::factory()->create();
        $ride  = Ride::factory()->create([
            'event_id'     => $event->id,
            'type'         => 'request',
            'edit_token'   => 'token123',
            'confirmed_at' => now(),
        ]);

        $this->postJson(
            "/api/e/{$event->slug}/rides/{$ride->id}/book?edit_token=token123",
            [],
            ['X-Locale' => 'en']
        )->assertStatus(422)->assertJsonPath('message', 'Only ride offers can be marked as fully booked.');
    }
}
