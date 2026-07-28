<?php

namespace Database\Factories;

use App\Enums\ChildGender;
use App\Models\Center;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
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
            'date_of_birth' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'gender' => fake()->randomElement(ChildGender::cases()),
            'photo_consent' => true,
        ];
    }
}
