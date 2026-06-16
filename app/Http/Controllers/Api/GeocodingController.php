<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:200']]);

        $baseUrl   = rtrim(env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'), '/');
        $userAgent = env('NOMINATIM_USER_AGENT', 'Lift2Event/1.0');

        $results = Http::withHeaders(['User-Agent' => $userAgent])
            ->get("$baseUrl/search", [
                'q'              => $request->string('q'),
                'format'         => 'json',
                'addressdetails' => 1,
                'limit'          => 5,
            ])
            ->json();

        return response()->json($results ?? []);
    }
}
