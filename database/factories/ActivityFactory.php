<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
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
            'title' => fake()->words(3, true),
            'tags' => fake()->randomElement(Activity::TAGS),
        ];
    }
}
