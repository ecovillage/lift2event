<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_configured_nominatim_url_and_user_agent(): void
    {
        config([
            'services.nominatim.url'        => 'https://nominatim.example.test/',
            'services.nominatim.user_agent' => 'TestAgent/1.0',
        ]);

        Http::fake([
            'nominatim.example.test/*' => Http::response([['display_name' => 'Berlin']]),
        ]);

        $this->getJson('/api/geocode/search?q=Berlin')
            ->assertOk()
            ->assertJson([['display_name' => 'Berlin']]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://nominatim.example.test/search?q=Berlin&format=json&addressdetails=1&limit=5'
                && $request->hasHeader('User-Agent', 'TestAgent/1.0');
        });
    }
}
