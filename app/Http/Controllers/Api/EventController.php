<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Event::with(['location', 'createdBy'])
            ->withCount('rides')
            ->orderByDesc('start_at');

        if (! $user->is_admin) {
            $query->where('created_by_id', $user->id);
        }

        return response()->json($query->get());
    }
}
