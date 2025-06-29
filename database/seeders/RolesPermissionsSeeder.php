<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $rolesToInsert = [];
        $permissionToInsert = [];

        // Get all specified guards from section 'guards' from file config/auth.php
        $guards = array_filter(array_keys(config('auth.guards')), fn ($value, $key) => $value !== 'sanctum', ARRAY_FILTER_USE_BOTH);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = collect(config('ehealth.roles'))->flatten()->unique()->toArray();

        // Prepare Role's and Permission's data to insert into DB
        foreach ($guards as $guard) {
            foreach (array_keys(config('ehealth.roles')) as $roleName) {
                $rolesToInsert[] = ['name' => $roleName, 'guard_name' => $guard];
            }

            foreach ($permissions as $permissionName) {
                $permissionToInsert[] = ['name' => $permissionName, 'guard_name' => $guard];
            }
        }

        Role::insert($rolesToInsert);

        Permission::insert($permissionToInsert);

        // update cache to know about the newly created permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign permissions for specified roles depends on the guard
        foreach ($guards as $guard) {
            $rolesByGuard = Role::with('permissions')
                ->whereIn('name', array_keys(config('ehealth.roles')))
                ->where('guard_name', $guard)
                ->get()
                ->keyBy('name');

            foreach (config('ehealth.roles') as $roleName => $permissions) {
                if ($rolesByGuard->has($roleName)) {
                    $rolesByGuard[$roleName]->givePermissionTo($permissions);
                }
            }
        }
    }
}
