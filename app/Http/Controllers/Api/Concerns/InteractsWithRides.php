<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Ride;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait InteractsWithRides
{
    private function rideRules(Request $request): array
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
            'contact_methods.*'     => [Rule::in(['email', 'signal', 'telegram', 'whatsapp', 'sms', 'call'])],
            'info'                  => ['nullable', 'string', 'max:2000'],
            'location'              => ['required', 'array'],
            'location.address'      => ['required', 'string', 'max:500'],
            'location.latitude'     => ['required', 'numeric', 'between:-90,90'],
            'location.longitude'    => ['required', 'numeric', 'between:-180,180'],
            'location.country_code' => ['nullable', 'string', 'size:2'],
        ];
    }

    private function applyRideUpdate(Ride $ride, array $data): Ride
    {
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

        return $ride->fresh()->load('location');
    }
}
