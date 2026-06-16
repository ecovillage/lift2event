<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Event::with(['location', 'createdBy'])
            ->withCount('rides')
            ->orderByDesc('start_at');

        if (! $user->is_admin) {
            $query->where('created_by_id', $user->id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'start_at'              => ['required', 'date'],
            'end_at'                => ['required', 'date', 'after:start_at'],
            'location'              => ['required', 'array'],
            'location.address'      => ['required', 'string', 'max:500'],
            'location.latitude'     => ['required', 'numeric', 'between:-90,90'],
            'location.longitude'    => ['required', 'numeric', 'between:-180,180'],
            'location.country_code' => ['nullable', 'string', 'size:2'],
        ]);

        $location = Location::create($data['location']);

        $event = Event::create([
            'name'          => $data['name'],
            'start_at'      => $data['start_at'],
            'end_at'        => $data['end_at'],
            'location_id'   => $location->id,
            'created_by_id' => $request->user()->id,
        ]);

        return response()->json($event->load('location'), 201);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        if (! $this->canAccess($request, $event)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($event->load(['location', 'createdBy', 'rides.location']));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        if (! $this->canAccess($request, $event)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'start_at'              => ['required', 'date'],
            'end_at'                => ['required', 'date', 'after:start_at'],
            'location'              => ['required', 'array'],
            'location.address'      => ['required', 'string', 'max:500'],
            'location.latitude'     => ['required', 'numeric', 'between:-90,90'],
            'location.longitude'    => ['required', 'numeric', 'between:-180,180'],
            'location.country_code' => ['nullable', 'string', 'size:2'],
        ]);

        $event->location->update($data['location']);
        $event->update([
            'name'     => $data['name'],
            'start_at' => $data['start_at'],
            'end_at'   => $data['end_at'],
        ]);

        return response()->json($event->load('location'));
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        if (! $this->canAccess($request, $event)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $event->delete();

        return response()->json(null, 204);
    }

    private function canAccess(Request $request, Event $event): bool
    {
        $user = $request->user();
        return $user->is_admin || $event->created_by_id === $user->id;
    }
}
