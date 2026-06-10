<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\Rack;
use App\Models\RackLevel;
use App\Models\RackSlot;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── WH001: Gudang Utama Jakarta ──────────────────────
        $wh1 = Warehouse::create([
            'code' => 'WH001',
            'name' => 'Gudang Utama Jakarta',
            'address' => 'Jl. Industri Raya No. 1, Jakarta',
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

        // ─── WH002: Gudang Distribusi Surabaya ───────────────
        $wh2 = Warehouse::create([
            'code' => 'WH002',
            'name' => 'Gudang Distribusi Surabaya',
            'address' => 'Jl. Logistik No. 5, Surabaya',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'postal_code' => '60123',
            'latitude' => -7.2575000,
            'longitude' => 112.7521000,
            'capacity_m2' => 5000,
            'warehouse_type' => 'reguler',
            'pic_name' => 'Siti Rahayu',
            'pic_phone' => '031-5555678',
            'pic_email' => 'siti@warehouse.local',
            'is_active' => true,
        ]);

        // ─── WH003: Gudang Cold Storage Bekasi ───────────────
        $wh3 = Warehouse::create([
            'code' => 'WH003',
            'name' => 'Gudang Cold Storage Bekasi',
            'address' => 'Jl. Industri Jababeka No. 10, Bekasi',
            'city' => 'Bekasi',
            'province' => 'Jawa Barat',
            'postal_code' => '17530',
            'latitude' => -6.2398000,
            'longitude' => 107.1483000,
            'capacity_m2' => 3000,
            'warehouse_type' => 'cold_storage',
            'pic_name' => 'Dewi Lestari',
            'pic_phone' => '021-5559012',
            'pic_email' => 'dewi@warehouse.local',
            'is_active' => true,
        ]);

        // ─── ZONES for WH001 ─────────────────────────────────
        $zoneA = Zone::create([
            'warehouse_id' => $wh1->id, 'code' => 'A', 'name' => 'Zona Fast Moving',
            'zone_type' => 'fast_moving', 'color' => '#3B82F6', 'sort_order' => 1,
        ]);
        $zoneB = Zone::create([
            'warehouse_id' => $wh1->id, 'code' => 'B', 'name' => 'Zona Slow Moving',
            'zone_type' => 'slow_moving', 'color' => '#10B981', 'sort_order' => 2,
        ]);
        $zoneC = Zone::create([
            'warehouse_id' => $wh1->id, 'code' => 'C', 'name' => 'Zona Barang Berat',
            'zone_type' => 'heavy', 'color' => '#F59E0B', 'sort_order' => 3,
        ]);

        // ─── ZONES for WH002 ─────────────────────────────────
        $zoneD = Zone::create([
            'warehouse_id' => $wh2->id, 'code' => 'A', 'name' => 'Zona Utama',
            'zone_type' => 'fast_moving', 'color' => '#6366F1', 'sort_order' => 1,
        ]);

        // ─── ZONES for WH003 (Cold Storage) ──────────────────
        $zoneE = Zone::create([
            'warehouse_id' => $wh3->id, 'code' => 'C1', 'name' => 'Zona Chiller 2-8°C',
            'zone_type' => 'cold', 'color' => '#06B6D4', 'sort_order' => 1,
            'temperature_range' => ['min' => 2, 'max' => 8, 'unit' => 'celsius'],
            'allowed_product_types' => ['food', 'fmcg'],
        ]);
        $zoneF = Zone::create([
            'warehouse_id' => $wh3->id, 'code' => 'C2', 'name' => 'Zona Frozen -18°C',
            'zone_type' => 'cold', 'color' => '#0891B2', 'sort_order' => 2,
            'temperature_range' => ['min' => -20, 'max' => -15, 'unit' => 'celsius'],
            'allowed_product_types' => ['food'],
        ]);

        // ─── RACKS for WH001 Zone A (Fast Moving) ────────────
        foreach (['A-01', 'A-02', 'A-03'] as $i => $rackCode) {
            $this->createRackWithSlots($zoneA, $rackCode, 100 + ($i * 200), 100, 300, 80, 400, 4, 5, 120, 90, 100, 100);
        }

        // ─── RACKS for WH001 Zone B (Slow Moving) ────────────
        foreach (['B-01', 'B-02'] as $i => $rackCode) {
            $this->createRackWithSlots($zoneB, $rackCode, 100 + ($i * 200), 400, 300, 80, 200, 3, 4, 100, 100, null, 100);
        }

        // ─── RACKS for WH001 Zone C (Heavy) ──────────────────
        foreach (['C-01', 'C-02'] as $i => $rackCode) {
            $this->createRackWithSlots($zoneC, $rackCode, 100 + ($i * 200), 700, 400, 120, 250, 2, 3, 150, 150, 200, 200);
        }

        // ─── RACKS for WH002 Zone A ──────────────────────────
        foreach (['A-01', 'A-02'] as $i => $rackCode) {
            $this->createRackWithSlots($zoneD, $rackCode, 100 + ($i * 200), 100, 300, 80, 200, 3, 4, 100, 100, null, 100);
        }

        // ─── RACKS for WH003 Zone C1 (Chiller) ──────────────
        foreach (['C1-01', 'C1-02'] as $i => $rackCode) {
            $this->createRackWithSlots($zoneE, $rackCode, 100 + ($i * 200), 100, 250, 80, 250, 4, 4, 100, 100, null, 80);
        }

        // ─── RACKS for WH003 Zone C2 (Frozen) ───────────────
        $this->createRackWithSlots($zoneF, 'C2-01', 100, 100, 250, 80, 250, 4, 4, 100, 100, null, 80);

        $this->command->info('Warehouses seeded: 3 warehouses with zones, racks, levels & slots');
    }

    private function createRackWithSlots(
        Zone $zone, string $code, int $x, int $y,
        int $width, int $depth, int $height,
        int $levelCount, int $slotsPerLevel,
        int $firstLevelHeight, int $otherLevelHeight,
        ?int $levelMaxWeight = null, ?int $slotMaxWeight = null
    ): void {
        $rack = Rack::create([
            'zone_id' => $zone->id,
            'code' => $code,
            'name' => "Rak {$code}",
            'canvas_x' => $x,
            'canvas_y' => $y,
            'width_cm' => $width,
            'depth_cm' => $depth,
            'height_cm' => $height,
        ]);

        for ($l = 1; $l <= $levelCount; $l++) {
            $level = RackLevel::create([
                'rack_id' => $rack->id,
                'level_number' => $l,
                'height_cm' => $l === 1 ? $firstLevelHeight : $otherLevelHeight,
                'max_weight_kg' => $levelMaxWeight ?? 500,
            ]);

            for ($s = 1; $s <= $slotsPerLevel; $s++) {
                RackSlot::create([
                    'rack_level_id' => $level->id,
                    'slot_code' => "{$code}-L{$l}-S{$s}",
                    'slot_number' => $s,
                    'max_weight_kg' => $slotMaxWeight ?? 100,
                ]);
            }
        }
    }
}
