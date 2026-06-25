<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse
    {
        $event = Event::with(['location', 'createdBy'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Only accessible when the creator is approved; admins are always visible
        if (! $event->createdBy->is_admin && ! $event->createdBy->approved) {
            abort(404);
        }

        $token = $request->query('edit_token', '');

        $rides = $event->rides()
            ->with('location')
            ->orderBy('type')
            ->orderByDesc('created_at')
            ->get()
            // Unconfirmed guest rides aren't shown to other visitors, except to
            // whoever holds the matching edit token (e.g. the edit/delete pages).
            ->filter(fn ($ride) => $ride->confirmed_at || ($token !== '' && hash_equals($ride->edit_token ?? '', $token)))
            ->values()
            ->makeHidden('edit_token');

        return response()->json([
            'event' => $event,
            'rides' => $rides,
        ]);
    }
}
