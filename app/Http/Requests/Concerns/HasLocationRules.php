<?php

namespace App\Http\Requests\Concerns;

trait HasLocationRules
{
    protected function locationRules(): array
    {
        return [
            'location'              => ['required', 'array'],
            'location.address'      => ['required', 'string', 'max:500'],
            'location.latitude'     => ['required', 'numeric', 'between:-90,90'],
            'location.longitude'    => ['required', 'numeric', 'between:-180,180'],
            'location.country_code' => ['nullable', 'string', 'size:2'],
            'location.street'       => ['nullable', 'string', 'max:255'],
            'location.house_number' => ['nullable', 'string', 'max:50'],
            'location.postal_code'  => ['nullable', 'string', 'max:20'],
            'location.city'         => ['nullable', 'string', 'max:255'],
            'location.display_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
