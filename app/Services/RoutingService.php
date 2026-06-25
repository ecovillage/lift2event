<?php

namespace App\Services;

use App\Models\Location;
use App\Models\LocationRoute;
use Illuminate\Support\Facades\Http;

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

        $baseUrl = rtrim(env('OPENROUTESERVICE_URL', 'https://api.openrouteservice.org'), '/');

        try {
            $response = Http::withHeaders(['Authorization' => env('OPENROUTESERVICE_API_KEY')])
                ->timeout(5)
                ->get("$baseUrl/v2/directions/driving-car", [
                    'start' => "{$from->longitude},{$from->latitude}",
                    'end'   => "{$to->longitude},{$to->latitude}",
                ]);
        } catch (\Throwable $e) {
            report($e);

            return ['geometry' => null];
        }

        if (! $response->successful()) {
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
