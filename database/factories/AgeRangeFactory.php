<?php

namespace Database\Factories;

use App\Models\AgeRange;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgeRange>
 */
class AgeRangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = fake()->numberBetween(0, 36);
        $max = $min + fake()->numberBetween(6, 30);

        return [
            'center_id' => Center::factory(),
            'label' => "{$min} m - {$max} m",
            'min_months' => $min,
            'max_months' => $max,
        ];
    }
}
