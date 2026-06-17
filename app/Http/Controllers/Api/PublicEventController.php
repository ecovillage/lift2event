<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class PublicEventController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $event = Event::with(['location', 'createdBy'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Only accessible when the creator is approved; admins are always visible
        if (! $event->createdBy->is_admin && ! $event->createdBy->approved) {
            abort(404);
        }

        $rides = $event->rides()
            ->with('location')
            ->orderBy('type')
            ->orderByDesc('created_at')
            ->get()
            ->makeHidden('edit_token');

        return response()->json([
            'event' => $event,
            'rides' => $rides,
        ]);
    }
}
