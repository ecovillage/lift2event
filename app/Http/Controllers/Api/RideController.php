<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\InteractsWithRides;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ride;
use App\Services\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RideController extends Controller
{
    use InteractsWithRides;

    public function update(Request $request, Event $event, Ride $ride): JsonResponse
    {
        if (! $this->canManage($request, $event, $ride)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data   = $request->validate($this->rideRules($request));
        $result = $this->applyRideUpdate($ride, $data);

        return response()->json($result->makeHidden('edit_token'));
    }

    public function destroy(Request $request, Event $event, Ride $ride): JsonResponse
    {
        if (! $this->canManage($request, $event, $ride)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ride->delete();

        return response()->json(null, 204);
    }

    public function route(Request $request, Event $event, Ride $ride, RoutingService $routingService): JsonResponse
    {
        if (! $this->canManage($request, $event, $ride)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($routingService->routeFor($ride->location, $event->location));
    }

    private function canManage(Request $request, Event $event, Ride $ride): bool
    {
        if ($ride->event_id !== $event->id) {
            abort(404);
        }

        $user = $request->user();

        return $user->is_admin || $event->created_by_id === $user->id;
    }
}
