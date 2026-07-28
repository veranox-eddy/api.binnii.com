<?php

namespace Database\Factories;

use App\Enums\EntryType;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
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
            'type' => EntryType::Activity,
            'occurred_at' => now(),
            'payload' => ['notes' => fake()->sentence()],
        ];
    }
}
