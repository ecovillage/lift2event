<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+6 months');
        $end   = (clone $start)->modify('+2 days');

        return [
            'name'          => fake()->sentence(3, false),
            'start_at'      => $start,
            'end_at'        => $end,
            'location_id'   => Location::factory(),
            'created_by_id' => User::factory(),
        ];
    }
}
