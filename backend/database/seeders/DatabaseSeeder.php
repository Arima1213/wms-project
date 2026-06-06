<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Product;
use App\Models\Rack;
use App\Models\RackLevel;
use App\Models\RackSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => 'Administrator',
            'email' => 'admin@wms.local',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Create sample warehouses
        $wh1 = Warehouse::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'code' => 'WH001',
            'name' => 'Gudang Utama Jakarta',
            'description' => 'Gudang penyimpanan utama di Jakarta',
            'type' => 'warehouse',
            'status' => 'active',
            'address' => ['street' => 'Jl. Industri Raya No. 1', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'postal_code' => '12345'],
            'contact' => ['name' => 'Budi Santoso', 'phone' => '021-5551234', 'email' => 'budi@warehouse.local'],
            'max_capacity' => 10000,
        ]);

        $wh2 = Warehouse::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'code' => 'WH002',
            'name' => 'Gudang Distribusi Surabaya',
            'description' => 'Gudang distribusi untuk area Jawa Timur',
            'type' => 'distribution',
            'status' => 'active',
            'address' => ['street' => 'Jl. Logística No. 5', 'city' => 'Surabaya', 'province' => 'Jawa Timur', 'postal_code' => '60123'],
            'contact' => ['name' => 'Siti Rahayu', 'phone' => '031-5555678', 'email' => 'siti@warehouse.local'],
            'max_capacity' => 5000,
        ]);

        // Create categories
        $cat1 = Category::create(['uuid' => \Illuminate\Support\Str::uuid(), 'name' => 'Elektronik', 'code' => 'ELEC', 'type' => 'product', 'is_active' => true]);
        $cat2 = Category::create(['uuid' => \Illuminate\Support\Str::uuid(), 'name' => 'Packaging', 'code' => 'PACK', 'type' => 'product', 'is_active' => true]);
        $cat3 = Category::create(['uuid' => \Illuminate\Support\Str::uuid(), 'name' => 'Bahan Baku', 'code' => 'RAWM', 'type' => 'product', 'is_active' => true]);

        // Create products
        $products = [
            ['sku' => 'ELEC-001', 'name' => 'Kabel USB Type-C 1M', 'category_id' => $cat1->id, 'unit' => 'pcs', 'min_stock' => 100, 'has_expiry' => false],
            ['sku' => 'ELEC-002', 'name' => 'Adapter Listrik 5V 2A', 'category_id' => $cat1->id, 'unit' => 'pcs', 'min_stock' => 50, 'has_expiry' => false],
            ['sku' => 'PACK-001', 'name' => 'Kotak Kardon 30x30x30', 'category_id' => $cat2->id, 'unit' => 'pcs', 'min_stock' => 200, 'has_expiry' => false],
            ['sku' => 'PACK-002', 'name' => 'Bubble Wrap Roll 50M', 'category_id' => $cat2->id, 'unit' => 'roll', 'min_stock' => 20, 'has_expiry' => false],
            ['sku' => 'RAWM-001', 'name' => 'Plastik PE 0.5mm', 'category_id' => $cat3->id, 'unit' => 'kg', 'min_stock' => 500, 'has_expiry' => true, 'shelf_life_days' => 365],
        ];

        foreach ($products as $p) {
            $hasExpiry = $p['has_expiry'] ?? false;
            Product::create(array_merge(['uuid' => \Illuminate\Support\Str::uuid(), 'is_active' => true], $p, ['has_expiry' => $hasExpiry]));
        }

        // Create racks for warehouse 1
        $rackCodes = ['A', 'B', 'C', 'D'];
        foreach ($rackCodes as $i => $code) {
            $rack = Rack::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'warehouse_id' => $wh1->id,
                'code' => $code,
                'name' => "Rak $code",
                'type' => 'standard',
                'zone' => $i < 2 ? 'Zone A' : 'Zone B',
                'levels' => 4,
                'slots_per_level' => 10,
                'status' => 'active',
                'position' => ['x' => $i * 150, 'y' => 0, 'width' => 140, 'height' => 400],
            ]);

            // Create levels
            for ($l = 1; $l <= 4; $l++) {
                $level = RackLevel::create([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'rack_id' => $rack->id,
                    'level_number' => $l,
                    'height' => 100,
                    'max_load' => 50,
                    'slots' => 10,
                    'slot_type' => 'bin',
                ]);

                // Create slots
                for ($s = 1; $s <= 10; $s++) {
                    RackSlot::create([
                        'uuid' => \Illuminate\Support\Str::uuid(),
                        'rack_level_id' => $level->id,
                        'slot_number' => $s,
                        'code' => "{$code}-{$l}-{$s}",
                        'barcode' => "{$code}{$l}{$s}",
                        'status' => 'empty',
                    ]);
                }
            }
        }

        $this->command->info('WMS database seeded successfully!');
        $this->command->info('Admin login: admin@wms.local / password123');
    }
}