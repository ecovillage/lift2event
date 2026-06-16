<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'address'      => fake()->address(),
            'latitude'     => fake()->latitude(),
            'longitude'    => fake()->longitude(),
            'country_code' => 'DE',
        ];
    }
}
