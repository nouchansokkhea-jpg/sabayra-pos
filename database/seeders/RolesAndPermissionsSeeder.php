<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Core permissions
        $permissions = [
            'manage_platform',
            'manage_businesses',
            'manage_subscriptions',
            'view_all_reports',
            'manage_products',
            'manage_categories',
            'manage_orders',
            'use_pos',
            'manage_inventory',
            'manage_recipes',
            'manage_suppliers',
            'manage_staff',
            'manage_settings',
            'view_business_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $owner->syncPermissions([
            'manage_products',
            'manage_categories',
            'manage_orders',
            'use_pos',
            'manage_inventory',
            'manage_recipes',
            'manage_suppliers',
            'manage_staff',
            'manage_settings',
            'view_business_reports',
        ]);

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'manage_products',
            'manage_categories',
            'manage_orders',
            'use_pos',
            'manage_inventory',
            'view_business_reports',
        ]);

        $cashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $cashier->syncPermissions([
            'use_pos',
            'manage_orders',
        ]);

        $waiter = Role::firstOrCreate(['name' => 'waiter', 'guard_name' => 'web']);
        $waiter->syncPermissions([
            'manage_orders',
        ]);

        $kitchen = Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'web']);
        $kitchen->syncPermissions([
            'manage_orders',
        ]);

        $inventory = Role::firstOrCreate(['name' => 'inventory', 'guard_name' => 'web']);
        $inventory->syncPermissions([
            'manage_inventory',
            'manage_recipes',
            'manage_suppliers',
        ]);
    }
}
