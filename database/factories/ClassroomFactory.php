<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
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
            'name' => fake()->unique()->words(2, true).' Room',
            'is_floating' => false,
            'photo_sharing_enabled' => true,
            'is_active' => true,
        ];
    }

    public function floating(): static
    {
        return $this->state(fn (array $attributes) => ['is_floating' => true]);
    }
}
