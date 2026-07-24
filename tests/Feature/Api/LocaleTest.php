<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the locale-switching mechanism itself (SetLocale middleware), not
 * the translated wording — that's a lang-file concern, not a test concern.
 * Two locales (de default, en override) are enough to prove switching works;
 * adding more supported languages later shouldn't require more tests here.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private const DEFAULT_MESSAGE  = 'Die eingegebenen Zugangsdaten sind falsch.';
    private const OVERRIDE_MESSAGE = 'The provided credentials are incorrect.';

    private function loginWithWrongPassword(array $headers = [])
    {
        return $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'], $headers);
    }

    public function test_defaults_to_configured_locale_without_header(): void
    {
        $this->loginWithWrongPassword()->assertJsonPath('message', self::DEFAULT_MESSAGE);
    }

    public function test_x_locale_header_switches_the_response_locale(): void
    {
        $this->loginWithWrongPassword(['X-Locale' => 'en'])->assertJsonPath('message', self::OVERRIDE_MESSAGE);
    }

    public function test_unsupported_locale_header_falls_back_to_default(): void
    {
        $this->loginWithWrongPassword(['X-Locale' => 'xx'])->assertJsonPath('message', self::DEFAULT_MESSAGE);
    }

    public function test_locale_does_not_leak_into_a_request_without_a_header(): void
    {
        $this->loginWithWrongPassword(['X-Locale' => 'en'])->assertJsonPath('message', self::OVERRIDE_MESSAGE);

        // A follow-up request with no header must not still be in English.
        $this->loginWithWrongPassword()->assertJsonPath('message', self::DEFAULT_MESSAGE);
    }

    public function test_authenticated_users_preferred_language_is_used(): void
    {
        $user  = User::factory()->create(['preferred_language' => 'en']);
        $token = $user->createToken('spa')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');
    }

    public function test_authenticated_users_preferred_language_takes_precedence_over_header(): void
    {
        $user  = User::factory()->create(['preferred_language' => 'en']);
        $token = $user->createToken('spa')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token", 'X-Locale' => 'de'])
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');
    }
}
