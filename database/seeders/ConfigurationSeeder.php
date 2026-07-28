<?php

namespace Database\Seeders;

use App\Enums\AccessLevel;
use App\Enums\UserType;
use App\Models\Center;
use App\Models\HealthScreening;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ConfigurationSeeder extends Seeder
{
    /**
     * Demo configuration: screening defaults, push preferences, virtual
     * areas and classroom-login users (logins-config.html shows only the
     * Floating Staff login as never-logged-in).
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();

        // Health screening config with the default question list.
        HealthScreening::firstOrCreate(
            ['center_id' => $center->id],
            ['staff_administered_enabled' => true, 'questions' => HealthScreening::DEFAULT_QUESTIONS],
        );

        // Push preferences: the admin row plus one per classroom.
        NotificationPreference::firstOrCreate(['center_id' => $center->id, 'classroom_id' => null]);
        foreach ($center->classrooms as $classroom) {
            NotificationPreference::firstOrCreate(
                ['center_id' => $center->id, 'classroom_id' => $classroom->id],
                ['new_likes' => ! $classroom->is_floating],
            );
        }

        // Virtual transition areas.
        foreach (['Playground', 'Gym'] as $i => $name) {
            $center->virtualAreas()->firstOrCreate(['name' => $name], ['sort_order' => $i + 1]);
        }

        // Classroom logins that HAVE logged in (Floating Staff never has).
        foreach (['bkmcci_infantroom', 'bkmcci_threetofiveroom'] as $username) {
            $classroom = $center->classrooms()->where('login_username', $username)->first();
            if (! $classroom) {
                continue;
            }

            $user = User::withTrashed()->firstOrNew([
                'organization_id' => $center->organization_id,
                'username' => $username,
            ]);
            if (! $user->exists) {
                $user->fill([
                    'name' => $classroom->name,
                    'email' => $username.'@classroom.login',
                    'password' => Hash::make('password'),
                    'last_active_at' => now()->subHours(2),
                ]);
                $user->forceFill(['type' => UserType::ClassroomLogin, 'access_level' => AccessLevel::Center])->save();
            }
        }
    }
}
