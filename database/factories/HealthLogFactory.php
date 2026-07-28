<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Classroom;
use App\Models\HealthLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthLog>
 */
class HealthLogFactory extends Factory
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
            'logged_at' => now(),
            'type' => 'symptom',
            'value' => fake()->randomElement(array_keys(HealthLog::SYMPTOMS)),
        ];
    }
}
