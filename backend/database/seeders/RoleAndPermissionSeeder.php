<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'warehouse' => ['view', 'create', 'update', 'delete'],
            'zone' => ['view', 'create', 'update', 'delete'],
            'rack' => ['view', 'create', 'update', 'delete'],
            'product' => ['view', 'create', 'update', 'delete', 'import'],
            'category' => ['view', 'create', 'update', 'delete'],
            'inbound' => ['view', 'create', 'update', 'delete', 'receive', 'cancel'],
            'outbound' => ['view', 'create', 'update', 'delete', 'pick', 'ship', 'cancel'],
            'transfer' => ['view', 'create', 'approve', 'reject', 'execute'],
            'stock_opname' => ['view', 'create', 'start', 'submit', 'approve'],
            'inventory' => ['view', 'adjust'],
            'planogram' => ['view', 'edit', 'snapshot'],
            'report' => ['view', 'export'],
            'user' => ['view', 'create', 'update', 'delete'],
            'role' => ['view', 'create', 'update', 'delete'],
            'audit_log' => ['view'],
            'webhook' => ['view', 'create', 'update', 'delete'],
            'dashboard' => ['view'],
            'setting' => ['view', 'update'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'sanctum',
                ], [
                    'group' => $module,
                ]);
            }
        }

        // Super Admin — all permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'sanctum'],
            ['description' => 'Full system access', 'is_system' => true]
        );
        $superAdmin->givePermissionTo(Permission::all());

        // Manager — most permissions except user/role/webhook management
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => 'sanctum'],
            ['description' => 'Warehouse manager — operational + reports']
        );
        $managerPerms = Permission::whereNotIn('group', ['user', 'role', 'webhook', 'setting'])->get();
        $managerRole->givePermissionTo($managerPerms);

        // Operator — operational permissions only (view + basic ops)
        $operatorRole = Role::firstOrCreate(
            ['name' => 'operator', 'guard_name' => 'sanctum'],
            ['description' => 'Warehouse operator — daily operations']
        );
        $operatorRole->givePermissionTo(
            Permission::where('name', 'like', '%.view')->get()
        );
        $operatorRole->givePermissionTo(
            Permission::whereIn('name', [
                'inbound.create', 'inbound.receive',
                'outbound.create', 'outbound.pick',
                'inventory.view',
                'planogram.view',
            ])->get()
        );

        // Viewer — read-only
        $viewerRole = Role::firstOrCreate(
            ['name' => 'viewer', 'guard_name' => 'sanctum'],
            ['description' => 'Read-only access']
        );
        $viewerRole->givePermissionTo(Permission::where('name', 'like', '%.view')->get());

        $this->command->info('Roles & Permissions seeded successfully.');
    }
}
