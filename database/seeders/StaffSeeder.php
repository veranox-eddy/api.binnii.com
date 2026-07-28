<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Enums\StaffStatus;
use App\Models\Center;
use App\Models\Classroom;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Seed the wireframe demo staff roster (staff-management.html), each
     * scheduled Monday–Friday in their homeroom.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();
        $classrooms = Classroom::where('center_id', $center->id)->pluck('id', 'name');

        $roster = [
            ['Joyce', 'Chang', 'Infant and Toddler Room', '2019-07-15'],
            ['David', 'Chen', 'Infant and Toddler Room', '2021-01-08'],
            ['Maria', 'Lopez', 'Three to Five Room', '2022-03-02'],
            ['Anna', 'Kim', 'Three to Five Room', '2023-09-01'],
        ];

        foreach ($roster as [$first, $last, $room, $hired]) {
            // Demo data must exist after seeding: restore it if it was soft-deleted.
            $staff = Staff::withTrashed()->firstOrCreate(
                ['center_id' => $center->id, 'first_name' => $first, 'last_name' => $last],
                [
                    'primary_classroom_id' => $classrooms[$room],
                    'status' => StaffStatus::Active,
                    'hired_on' => $hired,
                ],
            );
            $staff->restore();

            // The roster's Room column and daychip both come from the
            // enrollment, the same way the profile form writes them.
            $enrollment = $staff->enrollments()->firstOrCreate(
                ['classroom_id' => $classrooms[$room]],
                ['status' => EnrollmentStatus::Active, 'start_date' => $hired],
            );
            $enrollment->syncDays([1, 2, 3, 4, 5]); // Mon–Fri
        }
    }
}
