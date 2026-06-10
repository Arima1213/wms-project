<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\RackSlot;
use App\Models\SlotStock;
use App\Models\ProductBarcode;
use Illuminate\Database\Seeder;

class SlotStockSeeder extends Seeder
{
    public function run(): void
    {
        // Place stocks in WH001 Zone A slots (fast-moving)
        $slots = RackSlot::where('slot_code', 'like', 'A-01-L1-S%')->orWhere('slot_code', 'like', 'A-02-L1-S%')->get();
        $allSlots = RackSlot::whereIn('slot_code', [
            'A-01-L1-S1', 'A-01-L1-S2', 'A-01-L1-S3', 'A-01-L1-S4', 'A-01-L1-S5',
            'A-02-L1-S1', 'A-02-L1-S2', 'A-02-L1-S3', 'A-02-L1-S4',
            'B-01-L1-S1', 'B-01-L1-S2', 'B-01-L1-S3',
            'C-01-L1-S1', 'C-01-L1-S2',
        ])->get()->keyBy('slot_code');

        $pcs = \App\Models\Uom::where('code', 'PCS')->first();
        $box = \App\Models\Uom::where('code', 'BOX')->first();
        $kg = \App\Models\Uom::where('code', 'KG')->first();
        $roll = \App\Models\Uom::where('code', 'ROLL')->first();
        $ltr = \App\Models\Uom::where('code', 'LTR')->first();

        $stocks = [
            // Fast-moving electronics in Zone A
            ['slot_code' => 'A-01-L1-S1', 'sku' => 'ELEC-001', 'qty' => 250, 'uom' => 'PCS', 'cost' => 15000],
            ['slot_code' => 'A-01-L1-S2', 'sku' => 'ELEC-002', 'qty' => 120, 'uom' => 'PCS', 'cost' => 35000],
            ['slot_code' => 'A-01-L1-S3', 'sku' => 'ELEC-003', 'qty' => 80,  'uom' => 'PCS', 'cost' => 125000],
            ['slot_code' => 'A-01-L1-S4', 'sku' => 'ELEC-004', 'qty' => 45,  'uom' => 'PCS', 'cost' => 250000],
            ['slot_code' => 'A-01-L1-S5', 'sku' => 'PACK-001', 'qty' => 500, 'uom' => 'PCS', 'cost' => 5000],

            // Packaging in Zone A
            ['slot_code' => 'A-02-L1-S1', 'sku' => 'PACK-002', 'qty' => 35,  'uom' => 'ROLL', 'cost' => 85000],
            ['slot_code' => 'A-02-L1-S2', 'sku' => 'PACK-003', 'qty' => 25,  'uom' => 'ROLL', 'cost' => 120000],
            ['slot_code' => 'A-02-L1-S3', 'sku' => 'PACK-004', 'qty' => 1000, 'uom' => 'PCS', 'cost' => 2500],
            ['slot_code' => 'A-02-L1-S4', 'sku' => 'FMCG-001', 'qty' => 80,  'uom' => 'BOX', 'cost' => 372000],

            // Slow-moving in Zone B
            ['slot_code' => 'B-01-L1-S1', 'sku' => 'FMCG-002', 'qty' => 200, 'uom' => 'BOX', 'cost' => 84000],
            ['slot_code' => 'B-01-L1-S2', 'sku' => 'FMCG-003', 'qty' => 150, 'uom' => 'BOX', 'cost' => 48000],
            ['slot_code' => 'B-01-L1-S3', 'sku' => 'FMCG-004', 'qty' => 60,  'uom' => 'BOX', 'cost' => 108000],

            // Heavy items in Zone C
            ['slot_code' => 'C-01-L1-S1', 'sku' => 'RAWM-001', 'qty' => 800, 'uom' => 'KG',  'cost' => 8500],
            ['slot_code' => 'C-01-L1-S2', 'sku' => 'RAWM-002', 'qty' => 600, 'uom' => 'KG',  'cost' => 12500],
        ];

        $uoms = [
            'PCS' => $pcs, 'BOX' => $box, 'KG' => $kg, 'ROLL' => $roll, 'LTR' => $ltr,
        ];

        $count = 0;
        foreach ($stocks as $s) {
            $slot = $allSlots->get($s['slot_code']);
            $product = Product::where('sku', $s['sku'])->first();
            if (!$slot || !$product) continue;

            // Find matching batch if product tracks batches
            $batch = null;
            if ($product->track_batch) {
                $batch = ProductBatch::where('product_id', $product->id)->first();
            }

            $uom = $uoms[$s['uom']] ?? $pcs;

            // Calculate qty in base uom
            $qtyInBase = $s['qty'];
            if ($uom && $uom->conversion_factor && $uom->conversion_factor != 1) {
                $qtyInBase = $s['qty'] * $uom->conversion_factor;
            }

            SlotStock::create([
                'slot_id' => $slot->id,
                'product_id' => $product->id,
                'batch_id' => $batch?->id,
                'quantity' => $s['qty'],
                'uom_id' => $uom?->id,
                'quantity_in_base_uom' => $qtyInBase,
                'unit_cost' => $s['cost'],
                'total_cost' => $s['cost'] * $s['qty'],
                'is_current' => true,
            ]);
            $count++;
        }

        $this->command->info("Slot stocks seeded: {$count} slot stock entries");
    }
}
