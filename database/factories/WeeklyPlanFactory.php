<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\WeeklyPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeeklyPlan>
 */
class WeeklyPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'week_start_date' => now()->startOfWeek()->toDateString(),
        ];
    }
}
