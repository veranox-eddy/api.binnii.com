<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * The three toggles offered on the Add user form (staff-adduser.html).
     */
    public const array PERMISSIONS = [
        'billing-payments' => 'Billing & payments',
        'child-classroom-data' => 'Child & classroom data',
        'center-email-notifications' => 'Center email notifications',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISSIONS) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Org Admin', 'Center Admin', 'Teacher'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Org admins carry every permission. Center Admin stays empty on
        // purpose: invited users get the Add-user form's toggles as DIRECT
        // permissions, so the role must not blanket-grant them.
        Role::findByName('Org Admin')->syncPermissions(array_keys(self::PERMISSIONS));
        Role::findByName('Teacher')->syncPermissions(['child-classroom-data']);
    }
}
