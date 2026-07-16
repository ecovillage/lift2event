<?php

namespace App\Services;

use App\Models\Location;
use App\Models\LocationRoute;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutingService
{
    public function routeFor(Location $from, Location $to): array
    {
        $cached = LocationRoute::where('from_location_id', $from->id)
            ->where('to_location_id', $to->id)
            ->first();

        if ($cached) {
            return ['geometry' => $cached->geometry];
        }

        $baseUrl = rtrim(config('services.openrouteservice.url'), '/');

        $requestHeaders = ['Authorization' => '[redacted]'];

        try {
            $response = Http::withHeaders(['Authorization' => config('services.openrouteservice.key')])
                ->timeout(5)
                ->post("$baseUrl/v2/directions/driving-car/geojson", [
                    'coordinates' => [
                        [(float) $from->longitude, (float) $from->latitude],
                        [(float) $to->longitude,   (float) $to->latitude],
                    ],
                    'radiuses' => [5000, 5000],
                ]);
        } catch (\Throwable $e) {
            Log::warning('ORS request failed', [
                'request_headers'  => $requestHeaders,
                'exception'        => $e->getMessage(),
            ]);

            return ['geometry' => null];
        }

        if (! $response->successful()) {
            Log::warning('ORS request unsuccessful', [
                'status'           => $response->status(),
                'request_headers'  => $requestHeaders,
                'response_headers' => $response->headers(),
                'response_body'    => $response->body(),
            ]);

            return ['geometry' => null];
        }

        $coordinates = $response->json('features.0.geometry.coordinates');

        if (! is_array($coordinates)) {
            return ['geometry' => null];
        }

        LocationRoute::create([
            'from_location_id' => $from->id,
            'to_location_id'   => $to->id,
            'geometry'         => $coordinates,
        ]);

        return ['geometry' => $coordinates];
    }
}
