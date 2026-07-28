<?php

namespace Database\Seeders;

use App\Enums\AccessLevel;
use App\Enums\UserType;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class FoundationSeeder extends Seeder
{
    /**
     * Seed the wireframe demo foundation: 1 organization, 1 center, 1 org-admin user.
     */
    public function run(): void
    {
        $organization = Organization::firstOrCreate(['name' => 'Childcare Centre Inc.']);

        // Demo data must exist after seeding: restore it if it was soft-deleted.
        $center = Center::withTrashed()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Childcare Centre Inc.'],
            [
                'external_ref' => '37152',
                'email' => 'center@childcare.com',
                'timezone' => 'America/Vancouver',
                'is_active' => true,
            ],
        );
        $center->restore();

        CenterSetting::firstOrCreate(
            ['center_id' => $center->id],
            [
                'auto_send_report_time' => '18:00:00',
                'parents_can_sign_in' => true,
                'classroom_access' => true,
                'staff_management_enabled' => true,
                // curriculum.html's activated view demos "13 days left".
                'curriculum_enabled' => true,
                'curriculum_trial_ends_on' => $center->now()->addDays(13)->toDateString(),
            ],
        );

        // Privilege fields are not mass-assignable — set them via forceFill.
        $admin = User::withTrashed()->firstOrNew(['email' => 'admin@childcare.test']);
        $admin->forceFill([
            'organization_id' => $organization->id,
            'name' => 'BKMCC',
            'email' => 'admin@childcare.test',
            'password' => 'password',
            'type' => UserType::Admin,
            'access_level' => AccessLevel::Organization,
            'is_active' => true,
            'deleted_at' => null,
        ])->save();

        $admin->syncRoles(['Org Admin']);
    }
}
