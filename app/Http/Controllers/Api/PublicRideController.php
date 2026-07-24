<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\InteractsWithRides;
use App\Http\Controllers\Controller;
use App\Http\Requests\RideRequest;
use App\Mail\RideConfirmation;
use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use App\Services\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicRideController extends Controller
{
    use InteractsWithRides;

    public function store(RideRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $data  = $request->validated();
        $user  = $request->user('sanctum');

        $location = Location::create($data['location']);

        $ride = Ride::create([
            'event_id'        => $event->id,
            'user_id'         => $user?->id,
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
            'locale'          => $data['locale'] ?? 'de',
            // Logged-in creators are trusted and go live immediately; guests
            // must confirm via the link in the confirmation email first.
            'confirmed_at'    => $user ? now() : null,
        ]);

        try {
            Mail::to($ride->email)->send(new RideConfirmation($ride, $event));
        } catch (\Throwable $e) {
            report($e);

            // Guests have no other way to ever confirm this ride, so an entry
            // they can't confirm shouldn't be kept around. Logged-in creators'
            // rides are already confirmed and stay valid without the email.
            if (! $user) {
                $ride->delete();
                $location->delete();

                return response()->json([
                    'message' => 'Die Bestätigungsmail konnte nicht versendet werden. Bitte versuche es später noch einmal.',
                ], 503);
            }
        }

        return response()->json($ride->load('location'), 201);
    }

    public function update(RideRequest $request, string $slug, Ride $ride): JsonResponse
    {
        $this->assertRideBelongsToEvent($slug, $ride);
        $this->assertValidEditToken($request, $ride);

        return response()->json($this->applyRideUpdate($ride, $request->validated()));
    }

    public function destroy(Request $request, string $slug, Ride $ride): JsonResponse
    {
        $this->assertRideBelongsToEvent($slug, $ride);
        $this->assertValidEditToken($request, $ride);

        $ride->delete();

        return response()->json(null, 204);
    }

    public function confirm(Request $request, string $slug, Ride $ride): JsonResponse
    {
        $this->assertRideBelongsToEvent($slug, $ride);
        $this->assertValidEditToken($request, $ride);

        if (! $ride->confirmed_at) {
            $ride->update(['confirmed_at' => now()]);
        }

        return response()->json($ride->fresh()->load('location'));
    }

    public function book(Request $request, string $slug, Ride $ride): JsonResponse
    {
        $this->assertRideBelongsToEvent($slug, $ride);
        $this->assertValidEditToken($request, $ride);

        if ($ride->type !== 'offer') {
            return response()->json(['message' => 'Nur Mitfahrangebote können als ausgebucht markiert werden.'], 422);
        }

        if ($ride->seats !== 0) {
            $ride->update(['seats' => 0]);
        }

        return response()->json($ride->fresh()->load('location'));
    }

    public function route(Request $request, string $slug, Ride $ride, RoutingService $routingService): JsonResponse
    {
        $this->assertRideBelongsToEvent($slug, $ride);

        $token = $request->query('edit_token', '');

        // Same visibility rule as PublicEventController::show: unconfirmed
        // guest rides aren't shown to other visitors.
        if (! $ride->confirmed_at && ! ($token !== '' && hash_equals($ride->edit_token ?? '', $token))) {
            abort(404);
        }

        return response()->json($routingService->routeFor($ride->location, $ride->event->location));
    }

    private function assertRideBelongsToEvent(string $slug, Ride $ride): void
    {
        abort_unless($ride->event->slug === $slug, 404);
    }

    private function assertValidEditToken(Request $request, Ride $ride): void
    {
        abort_unless($ride->edit_token && hash_equals($ride->edit_token, $request->input('edit_token', '')), 403);
    }
}
