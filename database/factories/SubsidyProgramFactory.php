<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\SubsidyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubsidyProgram>
 */
class SubsidyProgramFactory extends Factory
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
            'name' => fake()->words(3, true).' Benefit',
        ];
    }
}
