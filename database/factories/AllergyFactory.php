<?php

namespace Database\Factories;

use App\Enums\AllergySeverity;
use App\Models\Allergy;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Allergy>
 */
class AllergyFactory extends Factory
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
            'note' => fake()->sentence(3),
            'severity' => fake()->randomElement(AllergySeverity::cases()),
        ];
    }
}
