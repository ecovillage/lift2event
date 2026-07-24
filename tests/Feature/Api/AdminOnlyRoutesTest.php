<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOnlyRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function auth(array $attributes = []): array
    {
        $user  = User::factory()->create($attributes);
        $token = $user->createToken('spa')->plainTextToken;

        return [$user, ['Authorization' => "Bearer $token"]];
    }

    public function test_non_admin_cannot_list_users(): void
    {
        [, $headers] = $this->auth();

        $this->getJson('/api/users', $headers)->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        [, $headers] = $this->auth(['is_admin' => true]);

        $this->getJson('/api/users', $headers)->assertOk();
    }

    public function test_non_admin_cannot_toggle_user_approval(): void
    {
        [, $headers] = $this->auth();
        $other = User::factory()->create();

        $this->putJson("/api/users/{$other->id}/approve", [], $headers)->assertForbidden();
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        [, $headers] = $this->auth();
        $other = User::factory()->create();

        $this->deleteJson("/api/users/{$other->id}", [], $headers)->assertForbidden();
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        [, $headers] = $this->auth();

        $this->putJson('/api/settings', ['map_zoom' => 5], $headers)->assertForbidden();
    }

    public function test_admin_can_update_settings(): void
    {
        [, $headers] = $this->auth(['is_admin' => true]);

        $this->putJson('/api/settings', ['map_zoom' => 5], $headers)
            ->assertOk()
            ->assertJsonPath('map_zoom', 5);
    }
}
