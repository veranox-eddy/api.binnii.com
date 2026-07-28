<?php

namespace Database\Seeders;

use App\Enums\DevelopmentalFramework;
use App\Models\AgeRange;
use App\Models\Center;
use App\Models\Classroom;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    /**
     * Seed the wireframe demo classrooms and the age-range options offered in
     * the Classroom Settings select (logins-config.html).
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();

        foreach ([
            ['All ages', null, null],
            ['0 m - 1 yr', 0, 12],
            ['0 m - 1 yr 6 m', 0, 18],
            ['1 yr 6 m - 2 yr 6 m', 18, 30],
            ['2 yr 6 m - 4 yr 6 m', 30, 54],
            ['2 yr 6 m - 5 yr', 30, 60],
        ] as [$label, $min, $max]) {
            AgeRange::firstOrCreate(
                ['center_id' => $center->id, 'label' => $label],
                ['min_months' => $min, 'max_months' => $max],
            );
        }

        // desired_capacity values are invented for the dashboard KPI demo —
        // the wireframes never state capacities (their ratio table shows
        // 13/23 students); the column itself is schema doc "classrooms".
        $rooms = [
            [
                'name' => 'Floating Staff',
                'login_username' => 'bkmcci_floatingstaff',
                'is_floating' => true,
            ],
            [
                'name' => 'Infant and Toddler Room',
                'display_name' => 'Infant Room Room',
                'external_ref' => '69874',
                'login_username' => 'bkmcci_infantroom',
                'desired_capacity' => 12,
                'developmental_framework' => DevelopmentalFramework::Age0To3,
            ],
            [
                'name' => 'Three to Five Room',
                'display_name' => 'Three to Five Room Room',
                'login_username' => 'bkmcci_threetofiveroom',
                'desired_capacity' => 25,
                'developmental_framework' => DevelopmentalFramework::Age3To6,
            ],
        ];

        foreach ($rooms as $room) {
            // Demo data must exist after seeding: restore it if it was soft-deleted.
            Classroom::withTrashed()->firstOrCreate(
                ['center_id' => $center->id, 'name' => $room['name']],
                $room,
            )->restore();
        }
    }
}
