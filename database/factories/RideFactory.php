<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class RideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'        => Event::factory(),
            'user_id'         => null,
            'location_id'     => Location::factory(),
            'type'            => fake()->randomElement(['offer', 'request']),
            'direction'       => fake()->randomElement(['both-ways', 'outbound-only', 'return-only']),
            'outbound_at'     => now()->addDays(7),
            'return_at'       => now()->addDays(9),
            'seats'           => fake()->numberBetween(1, 6),
            'name'            => fake()->name(),
            'email'           => fake()->safeEmail(),
            'phone'           => null,
            'contact_methods' => ['email'],
            'info'            => null,
            'edit_token'      => null,
        ];
    }
}
