<?php

namespace Database\Factories;

use App\Enums\StaffStatus;
use App\Models\Center;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => StaffStatus::Active,
            'hired_on' => fake()->dateTimeBetween('-5 years')->format('Y-m-d'),
        ];
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffStatus::Deactivated,
            'deactivated_on' => now()->toDateString(),
        ]);
    }
}
