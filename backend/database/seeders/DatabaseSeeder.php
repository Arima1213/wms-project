<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Uom;
use App\Models\Rack;
use App\Models\RackLevel;
use App\Models\RackSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Roles & Permissions ─────────────────────────────
        $this->seedRolesAndPermissions();

        // ─── Users ───────────────────────────────────────────
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@wms.local',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        $manager = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@wms.local',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $manager->assignRole('manager');

        $operator = User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@wms.local',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $operator->assignRole('operator');

        // ─── UOMs ────────────────────────────────────────────
        $pcs = Uom::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs', 'type' => 'unit']);
        $kg = Uom::create(['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight']);
        $box = Uom::create(['code' => 'BOX', 'name' => 'Box', 'symbol' => 'box', 'type' => 'unit', 'conversion_factor' => 24, 'base_uom_id' => $pcs->id]);
        $roll = Uom::create(['code' => 'ROLL', 'name' => 'Roll', 'symbol' => 'roll', 'type' => 'unit']);
        $ltr = Uom::create(['code' => 'LTR', 'name' => 'Liter', 'symbol' => 'L', 'type' => 'volume']);

        // ─── Product Categories ──────────────────────────────
        $catElec = ProductCategory::create(['code' => 'ELEC', 'name' => 'Elektronik']);
        $catPack = ProductCategory::create(['code' => 'PACK', 'name' => 'Packaging']);
        $catRawm = ProductCategory::create(['code' => 'RAWM', 'name' => 'Bahan Baku']);
        $catFmcg = ProductCategory::create(['code' => 'FMCG', 'name' => 'Fast Moving Consumer Goods']);

        // ─── Products ────────────────────────────────────────
        $products = [
            ['code' => 'PRD-0001', 'sku' => 'ELEC-001', 'name' => 'Kabel USB Type-C 1M', 'category_id' => $catElec->id, 'unit_id' => $pcs->id, 'min_stock' => 100, 'max_stock' => 1000, 'weight_kg' => 0.05],
            ['code' => 'PRD-0002', 'sku' => 'ELEC-002', 'name' => 'Adapter Listrik 5V 2A', 'category_id' => $catElec->id, 'unit_id' => $pcs->id, 'min_stock' => 50, 'max_stock' => 500, 'weight_kg' => 0.08],
            ['code' => 'PRD-0003', 'sku' => 'PACK-001', 'name' => 'Kotak Karton 30x30x30', 'category_id' => $catPack->id, 'unit_id' => $pcs->id, 'min_stock' => 200, 'max_stock' => 2000, 'length_cm' => 30, 'width_cm' => 30, 'height_cm' => 30],
            ['code' => 'PRD-0004', 'sku' => 'PACK-002', 'name' => 'Bubble Wrap Roll 50M', 'category_id' => $catPack->id, 'unit_id' => $roll->id, 'min_stock' => 20, 'max_stock' => 100],
            ['code' => 'PRD-0005', 'sku' => 'RAWM-001', 'name' => 'Plastik PE 0.5mm', 'category_id' => $catRawm->id, 'unit_id' => $kg->id, 'min_stock' => 500, 'max_stock' => 5000, 'track_batch' => true, 'track_expiry' => true],
            ['code' => 'PRD-0006', 'sku' => 'FMCG-001', 'name' => 'Susu UHT Full Cream 1L', 'category_id' => $catFmcg->id, 'unit_id' => $box->id, 'min_stock' => 100, 'max_stock' => 2000, 'track_batch' => true, 'track_expiry' => true],
            ['code' => 'PRD-0007', 'sku' => 'FMCG-002', 'name' => 'Mie Instan Goreng', 'category_id' => $catFmcg->id, 'unit_id' => $box->id, 'min_stock' => 200, 'max_stock' => 5000, 'track_batch' => true, 'track_expiry' => true],
            ['code' => 'PRD-0008', 'sku' => 'ELEC-003', 'name' => 'Mouse Wireless Bluetooth', 'category_id' => $catElec->id, 'unit_id' => $pcs->id, 'min_stock' => 30, 'max_stock' => 300, 'weight_kg' => 0.12],
        ];

        foreach ($products as $p) {
            Product::create(array_merge(['is_active' => true, 'barcode' => 'BC' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT)], $p));
        }

        // ─── Warehouses ──────────────────────────────────────
        $wh1 = Warehouse::create([
            'code' => 'WH001',
            'name' => 'Gudang Utama Jakarta',
            'address' => 'Jl. Industri Raya No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
            'latitude' => -6.2088000,
            'longitude' => 106.8456000,
            'capacity_m2' => 10000,
            'warehouse_type' => 'reguler',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '021-5551234',
            'pic_email' => 'budi@warehouse.local',
            'is_active' => true,
        ]);

        $wh2 = Warehouse::create([
            'code' => 'WH002',
            'name' => 'Gudang Distribusi Surabaya',
            'address' => 'Jl. Logistik No. 5',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'postal_code' => '60123',
            'latitude' => -7.2575000,
            'longitude' => 112.7521000,
            'capacity_m2' => 5000,
            'warehouse_type' => 'reguler',
            'pic_name' => 'Siti Rahayu',
            'pic_phone' => '031-5555678',
            'is_active' => true,
        ]);

        // ─── Zones for WH001 ────────────────────────────────
        $zoneA = Zone::create(['warehouse_id' => $wh1->id, 'code' => 'A', 'name' => 'Zona Fast Moving', 'zone_type' => 'fast_moving', 'color' => '#3B82F6', 'sort_order' => 1]);
        $zoneB = Zone::create(['warehouse_id' => $wh1->id, 'code' => 'B', 'name' => 'Zona Slow Moving', 'zone_type' => 'slow_moving', 'color' => '#10B981', 'sort_order' => 2]);
        $zoneC = Zone::create(['warehouse_id' => $wh1->id, 'code' => 'C', 'name' => 'Zona Barang Berat', 'zone_type' => 'heavy', 'color' => '#F59E0B', 'sort_order' => 3]);

        // Zones for WH002
        $zoneD = Zone::create(['warehouse_id' => $wh2->id, 'code' => 'A', 'name' => 'Zona Utama', 'zone_type' => 'fast_moving', 'color' => '#6366F1', 'sort_order' => 1]);

        // ─── Racks, Levels, Slots for WH001 Zone A ──────────
        foreach (['A-01', 'A-02', 'A-03'] as $i => $rackCode) {
            $rack = Rack::create([
                'zone_id' => $zoneA->id,
                'code' => $rackCode,
                'name' => "Rak {$rackCode}",
                'canvas_x' => 100 + ($i * 200),
                'canvas_y' => 100,
                'width_cm' => 300,
                'depth_cm' => 80,
                'height_cm' => 400,
            ]);

            for ($l = 1; $l <= 4; $l++) {
                $level = RackLevel::create([
                    'rack_id' => $rack->id,
                    'level_number' => $l,
                    'height_cm' => $l === 1 ? 120 : 90,
                    'max_weight_kg' => 500,
                ]);

                for ($s = 1; $s <= 5; $s++) {
                    RackSlot::create([
                        'rack_level_id' => $level->id,
                        'slot_code' => "{$rackCode}-L{$l}-S{$s}",
                        'slot_number' => $s,
                        'max_weight_kg' => 100,
                    ]);
                }
            }
        }

        // Racks for Zone B
        foreach (['B-01', 'B-02'] as $i => $rackCode) {
            $rack = Rack::create([
                'zone_id' => $zoneB->id,
                'code' => $rackCode,
                'name' => "Rak {$rackCode}",
                'canvas_x' => 100 + ($i * 200),
                'canvas_y' => 400,
                'width_cm' => 300,
                'depth_cm' => 80,
            ]);

            for ($l = 1; $l <= 3; $l++) {
                $level = RackLevel::create([
                    'rack_id' => $rack->id,
                    'level_number' => $l,
                    'height_cm' => 100,
                ]);

                for ($s = 1; $s <= 4; $s++) {
                    RackSlot::create([
                        'rack_level_id' => $level->id,
                        'slot_code' => "{$rackCode}-L{$l}-S{$s}",
                        'slot_number' => $s,
                    ]);
                }
            }
        }

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║   WMS Database Seeded Successfully!         ║');
        $this->command->info('╠══════════════════════════════════════════════╣');
        $this->command->info('║   Admin:    admin@wms.local / password123   ║');
        $this->command->info('║   Manager:  budi@wms.local  / password123   ║');
        $this->command->info('║   Operator: siti@wms.local  / password123   ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
    }

    private function seedRolesAndPermissions(): void
    {
        // Reset cached roles and permissions
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
                Permission::create([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                    'group' => $module,
                ]);
            }
        }

        // Super Admin — all permissions
        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web', 'description' => 'Full system access', 'is_system' => true]);
        $superAdmin->givePermissionTo(Permission::all());

        // Manager — most permissions except user/role/webhook management
        $managerRole = Role::create(['name' => 'manager', 'guard_name' => 'web', 'description' => 'Warehouse manager']);
        $managerPerms = Permission::whereNotIn('group', ['user', 'role', 'webhook', 'setting'])->get();
        $managerRole->givePermissionTo($managerPerms);

        // Operator — operational permissions only
        $operatorRole = Role::create(['name' => 'operator', 'guard_name' => 'web', 'description' => 'Warehouse operator']);
        $operatorPerms = Permission::whereIn('group', ['warehouse', 'zone', 'rack', 'product', 'category', 'inbound', 'outbound', 'inventory', 'planogram', 'dashboard'])
            ->whereIn('name', function ($q) {
                $q->select('name')->from('permissions')
                    ->where('name', 'like', '%.view')
                    ->orWhere('name', 'like', '%.create')
                    ->orWhere('name', 'like', '%.update')
                    ->orWhere('name', 'like', '%.receive')
                    ->orWhere('name', 'like', '%.pick');
            })->get();
        // Simplified: give view + basic ops
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

        // Viewer — view only
        $viewerRole = Role::create(['name' => 'viewer', 'guard_name' => 'web', 'description' => 'Read-only access']);
        $viewerRole->givePermissionTo(Permission::where('name', 'like', '%.view')->get());
    }
}