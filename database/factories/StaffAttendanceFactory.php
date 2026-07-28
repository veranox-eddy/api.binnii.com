<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffAttendance>
 */
class StaffAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'work_date' => now()->toDateString(),
            'clock_in_at' => now()->setTime(8, 30),
        ];
    }
}
