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

        // Collect all unique permissions from the new structured config
        $scopesByLglEntityType = config('ehealth.scopes_by_legal_entity_type', []);
        $baseScopes = config('ehealth.base_scopes', []);

        $allPermissions = collect($baseScopes);

        foreach ($scopesByLglEntityType as $type => $roles) {
            foreach ($roles as $roleName => $scopes) {
                $allPermissions = $allPermissions->merge($scopes);
            }
        }

        $permissions = $allPermissions->unique()->values()->all();

        // Prepare Role's and Permission's data to insert into DB.
        // We still use the legacy 'roles' config key to know which roles to create.
        $rolesToCreate = array_keys(config('ehealth.roles', []));

        foreach ($guards as $guard) {
            foreach ($rolesToCreate as $roleName) {
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
        // This part continues to use the legacy 'roles' config which now contains the superset
        // of all permissions for each role, ensuring backward compatibility.
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
