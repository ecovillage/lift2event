<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\InteractsWithRides;
use App\Http\Controllers\Controller;
use App\Http\Requests\RideRequest;
use App\Models\Event;
use App\Models\Ride;
use App\Services\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RideController extends Controller
{
    use InteractsWithRides;

    public function update(RideRequest $request, Event $event, Ride $ride): JsonResponse
    {
        $this->assertRideBelongsToEvent($event, $ride);
        $this->authorize('manage', $ride);

        $result = $this->applyRideUpdate($ride, $request->validated());

        return response()->json($result->makeHidden('edit_token'));
    }

    public function destroy(Request $request, Event $event, Ride $ride): JsonResponse
    {
        $this->assertRideBelongsToEvent($event, $ride);
        $this->authorize('manage', $ride);

        $ride->delete();

        return response()->json(null, 204);
    }

    public function route(Request $request, Event $event, Ride $ride, RoutingService $routingService): JsonResponse
    {
        $this->assertRideBelongsToEvent($event, $ride);
        $this->authorize('manage', $ride);

        return response()->json($routingService->routeFor($ride->location, $event->location));
    }

    private function assertRideBelongsToEvent(Event $event, Ride $ride): void
    {
        abort_unless($ride->event_id === $event->id, 404);
    }
}
