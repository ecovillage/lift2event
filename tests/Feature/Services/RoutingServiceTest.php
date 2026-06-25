<?php

namespace Tests\Feature\Services;

use App\Models\Location;
use App\Services\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function orsResponse(array $coordinates): array
    {
        return [
            'features' => [
                ['geometry' => ['coordinates' => $coordinates]],
            ],
        ];
    }

    public function test_cache_miss_fetches_from_ors_and_stores_result(): void
    {
        $coordinates = [[13.4, 52.5], [11.6, 48.1]];
        Http::fake(['*' => Http::response($this->orsResponse($coordinates))]);

        $from = Location::factory()->create(['latitude' => 52.5, 'longitude' => 13.4]);
        $to   = Location::factory()->create(['latitude' => 48.1, 'longitude' => 11.6]);

        $result = app(RoutingService::class)->routeFor($from, $to);

        $this->assertSame($coordinates, $result['geometry']);
        $this->assertDatabaseHas('location_routes', [
            'from_location_id' => $from->id,
            'to_location_id'   => $to->id,
        ]);
        Http::assertSentCount(1);
    }

    public function test_cache_hit_does_not_call_ors_again(): void
    {
        Http::fake(['*' => Http::response($this->orsResponse([[13.4, 52.5], [11.6, 48.1]]))]);

        $from    = Location::factory()->create();
        $to      = Location::factory()->create();
        $service = app(RoutingService::class);

        $first  = $service->routeFor($from, $to);
        $second = $service->routeFor($from, $to);

        $this->assertSame($first['geometry'], $second['geometry']);
        Http::assertSentCount(1);
    }

    public function test_ors_failure_returns_null_geometry_without_caching(): void
    {
        Http::fake(['*' => Http::response(null, 500)]);

        $from = Location::factory()->create();
        $to   = Location::factory()->create();

        $result = app(RoutingService::class)->routeFor($from, $to);

        $this->assertNull($result['geometry']);
        $this->assertDatabaseCount('location_routes', 0);
    }

    public function test_network_exception_returns_null_geometry_without_caching(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $from = Location::factory()->create();
        $to   = Location::factory()->create();

        $result = app(RoutingService::class)->routeFor($from, $to);

        $this->assertNull($result['geometry']);
        $this->assertDatabaseCount('location_routes', 0);
    }
}
