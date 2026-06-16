<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // POST /api/register
    // -------------------------------------------------------------------------

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Max Muster',
            'email'                 => 'max@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.email', 'max@example.com')
            ->assertJsonPath('user.is_admin', false)
            ->assertJsonPath('user.approved', false)
            ->assertJsonPath('user.preferred_language', 'de');

        $this->assertDatabaseHas('users', ['email' => 'max@example.com']);
    }

    public function test_register_token_authenticates_against_user_endpoint(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Max Muster',
            'email'                 => 'max@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $token = $response->json('token');

        $this->getJson('/api/user', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('email', 'max@example.com');
    }

    public function test_register_requires_name(): void
    {
        $this->postJson('/api/register', [
            'email'                 => 'x@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_register_requires_valid_email(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'Max',
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name'                  => 'Max',
            'email'                 => 'taken@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_password_shorter_than_8_chars(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'Max',
            'email'                 => 'x@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_mismatched_password_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'Max',
            'email'                 => 'x@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_missing_password_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name'     => 'Max',
            'email'    => 'x@example.com',
            'password' => 'secret123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // POST /api/login
    // -------------------------------------------------------------------------

    public function test_login_returns_token_and_full_user_data(): void
    {
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => 'secret123',
            'is_admin' => true,
            'approved' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'is_admin', 'approved', 'preferred_language']])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonPath('user.approved', true);
    }

    public function test_login_token_grants_access_to_protected_routes(): void
    {
        User::factory()->create(['email' => 'u@example.com', 'password' => 'secret123']);

        $token = $this->postJson('/api/login', ['email' => 'u@example.com', 'password' => 'secret123'])
            ->json('token');

        $this->getJson('/api/user', ['Authorization' => "Bearer $token"])
            ->assertOk();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'u@example.com', 'password' => 'correct']);

        $this->postJson('/api/login', ['email' => 'u@example.com', 'password' => 'wrong'])
            ->assertUnauthorized();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'secret123'])
            ->assertUnauthorized();
    }

    public function test_login_requires_email_field(): void
    {
        $this->postJson('/api/login', ['password' => 'secret123'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password_field(): void
    {
        $this->postJson('/api/login', ['email' => 'u@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_login_does_not_expose_password_hash(): void
    {
        User::factory()->create(['email' => 'u@example.com', 'password' => 'secret123']);

        $response = $this->postJson('/api/login', ['email' => 'u@example.com', 'password' => 'secret123']);

        $this->assertArrayNotHasKey('password', $response->json('user'));
    }

    // -------------------------------------------------------------------------
    // POST /api/logout
    // -------------------------------------------------------------------------

    public function test_logout_returns_ok_and_deletes_token(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token"])
            ->assertOk();

        // Token must be gone from DB
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_only_deletes_current_token_not_others(): void
    {
        $user   = User::factory()->create();
        $token1 = $user->createToken('spa-1')->plainTextToken;
        $token2 = $user->createToken('spa-2')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token1"])
            ->assertOk();

        // One token remains
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // The remaining token still works
        $this->getJson('/api/user', ['Authorization' => "Bearer $token2"])
            ->assertOk();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized();
    }

    public function test_revoked_token_cannot_access_protected_routes(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        // Delete the token directly in DB to simulate revocation without
        // reusing the same in-process HTTP client (which caches the auth guard)
        PersonalAccessToken::query()->delete();

        $this->getJson('/api/user', ['Authorization' => "Bearer $token"])
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/user
    // -------------------------------------------------------------------------

    public function test_user_endpoint_returns_authenticated_user(): void
    {
        $user  = User::factory()->create(['email' => 'me@example.com']);
        $token = $user->createToken('spa')->plainTextToken;

        $this->getJson('/api/user', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('email', 'me@example.com')
            ->assertJsonStructure(['id', 'name', 'email', 'is_admin', 'approved', 'preferred_language']);
    }

    public function test_user_endpoint_does_not_expose_password(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $response = $this->getJson('/api/user', ['Authorization' => "Bearer $token"]);
        $this->assertArrayNotHasKey('password', $response->json());
    }

    public function test_user_endpoint_rejects_request_without_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_endpoint_rejects_invalid_token(): void
    {
        $this->getJson('/api/user', ['Authorization' => 'Bearer invalid-token-xyz'])
            ->assertUnauthorized();
    }

    public function test_user_endpoint_rejects_malformed_authorization_header(): void
    {
        $this->getJson('/api/user', ['Authorization' => 'NotBearer abc'])
            ->assertUnauthorized();
    }
}
