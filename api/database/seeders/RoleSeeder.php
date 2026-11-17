<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // -------------------------------------------------------------------------
        // Permissions
        // -------------------------------------------------------------------------
        $permissions = [
            // Organization
            'organization.view', 'organization.edit', 'organization.delete',
            // Members
            'members.view', 'members.invite', 'members.remove', 'members.role.update',
            // Products (Sprint 2)
            'products.view', 'products.create', 'products.edit', 'products.delete',
            // Orders (Sprint 7)
            'orders.view', 'orders.manage',
            // AI (Sprint 3+)
            'ai.generate', 'ai.usage.view',
            // Analytics (Sprint 8)
            'analytics.view',
            // Admin
            'admin.super',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'sanctum',
            ]);
        }

        // -------------------------------------------------------------------------
        // Roles
        // -------------------------------------------------------------------------

        /** Super Admin — full platform access */
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions(Permission::all());

        /** Org Admin — manages their organization */
        $orgAdmin = Role::firstOrCreate(['name' => 'org_admin', 'guard_name' => 'sanctum']);
        $orgAdmin->syncPermissions([
            'organization.view', 'organization.edit',
            'members.view', 'members.invite', 'members.remove', 'members.role.update',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'orders.view', 'orders.manage',
            'ai.generate', 'ai.usage.view',
            'analytics.view',
        ]);

        /** Store Manager — manages products and orders */
        $storeManager = Role::firstOrCreate(['name' => 'store_manager', 'guard_name' => 'sanctum']);
        $storeManager->syncPermissions([
            'organization.view',
            'members.view',
            'products.view', 'products.create', 'products.edit',
            'orders.view', 'orders.manage',
            'ai.generate',
        ]);

        /** Customer — browse and shop */
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'sanctum']);
        $customer->syncPermissions([
            'products.view',
            'orders.view',
        ]);

        $this->command->info('✅ Roles and permissions seeded successfully.');
    }
}
