<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'type_of_incident' => fake()->randomElement(['Fall', 'Bump', 'Scratch', 'Bite']),
            'occurred_at' => now(),
        ];
    }
}
