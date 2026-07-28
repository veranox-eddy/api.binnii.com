<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\DailyReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
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
            'report_date' => now()->toDateString(),
        ];
    }
}
