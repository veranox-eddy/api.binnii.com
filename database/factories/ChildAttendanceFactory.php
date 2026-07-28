<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Child;
use App\Models\ChildAttendance;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChildAttendance>
 */
class ChildAttendanceFactory extends Factory
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
            'attendance_date' => now()->toDateString(),
            'check_in_at' => now()->setTime(9, 0),
            'check_in_by' => fake()->name(),
            'status' => AttendanceStatus::Present,
        ];
    }
}
