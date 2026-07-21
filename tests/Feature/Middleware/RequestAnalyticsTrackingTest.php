<?php

namespace Tests\Feature\Middleware;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestAnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    // Requests without a browser-like User-Agent are dropped as bots regardless of route.
    private const BROWSER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        // The anonymized test IP (127.0.0.0) doesn't match the package's
        // exact-match localhost check, so it would otherwise try a real
        // geolocation HTTP call, which Http::preventStrayRequests() blocks.
        config(['request-analytics.geolocation.enabled' => false]);
    }

    private function browserHeaders(): array
    {
        return ['User-Agent' => self::BROWSER_USER_AGENT];
    }

    public function test_event_page_view_is_tracked(): void
    {
        $event = Event::factory()->create();

        $this->get("/e/{$event->slug}", $this->browserHeaders())->assertOk();

        $this->assertDatabaseHas('request_analytics', ['path' => "e/{$event->slug}"]);
    }

    public function test_event_sub_page_view_is_tracked(): void
    {
        $event = Event::factory()->create();

        $this->get("/e/{$event->slug}/ride/1/edit", $this->browserHeaders())->assertOk();

        $this->assertDatabaseHas('request_analytics', ['path' => "e/{$event->slug}/ride/1/edit"]);
    }

    public function test_backend_page_view_is_tracked(): void
    {
        $this->get('/backend/events', $this->browserHeaders())->assertOk();

        $this->assertDatabaseHas('request_analytics', ['path' => 'backend/events']);
    }

    public function test_backend_root_view_is_tracked(): void
    {
        $this->get('/backend', $this->browserHeaders())->assertOk();

        $this->assertDatabaseHas('request_analytics', ['path' => 'backend']);
    }

    public function test_login_page_view_is_not_tracked(): void
    {
        $this->get('/login', $this->browserHeaders())->assertOk();

        $this->assertDatabaseCount('request_analytics', 0);
    }

    public function test_unmatched_spa_path_is_not_tracked(): void
    {
        $this->get('/some-random-unmatched-path', $this->browserHeaders())->assertOk();

        $this->assertDatabaseCount('request_analytics', 0);
    }

    public function test_api_request_is_not_tracked(): void
    {
        $this->getJson('/api/settings', $this->browserHeaders())->assertOk();

        $this->assertDatabaseCount('request_analytics', 0);
    }

    public function test_public_event_api_request_is_not_tracked(): void
    {
        $event = Event::factory()->create();

        $this->getJson("/api/e/{$event->slug}", $this->browserHeaders())->assertOk();

        $this->assertDatabaseCount('request_analytics', 0);
    }
}
