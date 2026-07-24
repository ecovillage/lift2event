<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
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
            ->withCount([
                'rides as offers_count'   => fn ($q) => $q->where('type', 'offer'),
                'rides as requests_count' => fn ($q) => $q->where('type', 'request'),
            ])
            ->orderByDesc('start_at');

        if (! $user->is_admin) {
            $query->where('created_by_id', $user->id);
        }

        return response()->json($query->get());
    }

    public function store(EventRequest $request): JsonResponse
    {
        $data = $request->validated();

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
        $this->authorize('manage', $event);

        $event->load(['location', 'createdBy', 'rides.location']);
        $event->rides->makeHidden('edit_token');

        return response()->json($event);
    }

    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $data = $request->validated();

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
        $this->authorize('manage', $event);

        $event->deleteWithLocations();

        return response()->json(null, 204);
    }
}
