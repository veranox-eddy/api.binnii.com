<?php

namespace Database\Factories;

use App\Enums\AbsenceReason;
use App\Models\Absence;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absence>
 */
class AbsenceFactory extends Factory
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
            'start_date' => now()->toDateString(),
            'reason' => fake()->randomElement(AbsenceReason::cases()),
        ];
    }
}
