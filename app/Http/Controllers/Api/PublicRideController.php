<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicRideController extends Controller
{
    private function rules(Request $request): array
    {
        $dir = $request->input('direction');

        return [
            'type'                  => ['required', Rule::in(['offer', 'request'])],
            'direction'             => ['required', Rule::in(['both-ways', 'outbound-only', 'return-only'])],
            'outbound_at'           => [
                in_array($dir, ['both-ways', 'outbound-only']) ? 'required' : 'nullable',
                'date',
            ],
            'return_at'             => [
                in_array($dir, ['both-ways', 'return-only']) ? 'required' : 'nullable',
                'date',
            ],
            'seats'                 => ['required', 'integer', 'min:1', 'max:8'],
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'max:200'],
            'phone'                 => ['nullable', 'string', 'max:50'],
            'contact_methods'       => ['required', 'array', 'min:1'],
            'contact_methods.*'     => [Rule::in(['email', 'phone', 'signal', 'telegram', 'whatsapp', 'sms', 'call'])],
            'info'                  => ['nullable', 'string', 'max:2000'],
            'location'              => ['required', 'array'],
            'location.address'      => ['required', 'string', 'max:500'],
            'location.latitude'     => ['required', 'numeric', 'between:-90,90'],
            'location.longitude'    => ['required', 'numeric', 'between:-180,180'],
            'location.country_code' => ['nullable', 'string', 'size:2'],
        ];
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $data  = $request->validate($this->rules($request));

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

        return response()->json($ride->load('location'), 201);
    }

    public function update(Request $request, string $slug, Ride $ride): JsonResponse
    {
        abort_unless($ride->event->slug === $slug, 404);

        if (! $ride->edit_token || ! hash_equals($ride->edit_token, $request->input('edit_token', ''))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate($this->rules($request));

        $ride->location->update($data['location']);
        $ride->update([
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
        ]);

        return response()->json($ride->fresh()->load('location'));
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
