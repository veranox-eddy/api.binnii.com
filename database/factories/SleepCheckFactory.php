<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Classroom;
use App\Models\SleepCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SleepCheck>
 */
class SleepCheckFactory extends Factory
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
            'classroom_id' => Classroom::factory(),
            'checked_at' => now(),
            'position' => fake()->randomElement(SleepCheck::POSITIONS),
            'status' => fake()->randomElement(SleepCheck::STATUSES),
        ];
    }
}
