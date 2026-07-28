<?php

namespace Database\Factories;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'child_first_name' => fake()->firstName(),
            'child_last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-5 years', '-6 months')->format('Y-m-d'),
            'stage' => ApplicationStage::Applicant,
            'status' => ApplicationStatus::New,
            'submitted_at' => now(),
        ];
    }
}
