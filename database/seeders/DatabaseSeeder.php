<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            FoundationSeeder::class,
            ClassroomSeeder::class,
            StaffSeeder::class,
            ChildSeeder::class,
            AttendanceSeeder::class,
            EntrySeeder::class,
            RegistrationSeeder::class,
            SubsidySeeder::class,
            MessagingSeeder::class,
            HealthSeeder::class,
            CurriculumSeeder::class,
            ConfigurationSeeder::class,
            MilestoneDefinitionSeeder::class,
        ]);
    }
}
