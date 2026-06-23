<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\InteractsWithRides;
use App\Http\Controllers\Controller;
use App\Mail\RideConfirmation;
use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicRideController extends Controller
{
    use InteractsWithRides;

    public function store(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $data  = $request->validate($this->rideRules($request));

        $location = Location::create($data['location']);

        $ride = Ride::create([
            'event_id'        => $event->id,
            'location_id'     => $location->id,
            'type'            => $data['type'],
            'direction'       => $data['direction'],
            'outbound_at'     => $data['outbound_at'] ?? null,
            'return_at'       => $data['return_at'] ?? null,
            'seats'           => $data['seats'],
            'name'            => $data['name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'contact_methods' => $data['contact_methods'],
            'info'            => $data['info'] ?? null,
            'edit_token'      => bin2hex(random_bytes(32)),
        ]);

        Mail::to($ride->email)->send(new RideConfirmation($ride, $event));

        return response()->json($ride->load('location'), 201);
    }

    public function update(Request $request, string $slug, Ride $ride): JsonResponse
    {
        abort_unless($ride->event->slug === $slug, 404);

        if (! $ride->edit_token || ! hash_equals($ride->edit_token, $request->input('edit_token', ''))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate($this->rideRules($request));

        return response()->json($this->applyRideUpdate($ride, $data));
    }

    public function destroy(Request $request, string $slug, Ride $ride): JsonResponse
    {
        abort_unless($ride->event->slug === $slug, 404);

        if (! $ride->edit_token || ! hash_equals($ride->edit_token, $request->input('edit_token', ''))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ride->delete();

        return response()->json(null, 204);
    }
}
