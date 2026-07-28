<?php

namespace Database\Factories;

use App\Enums\MenusCalendarType;
use App\Models\Center;
use App\Models\MenusCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenusCalendar>
 */
class MenusCalendarFactory extends Factory
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
            'type' => MenusCalendarType::Calendar,
            'name' => fake()->words(2, true).' Calendar',
        ];
    }
}
