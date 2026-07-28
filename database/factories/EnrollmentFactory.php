<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
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
            'status' => EnrollmentStatus::Active,
            'enrolled_on' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
        ];
    }
}
