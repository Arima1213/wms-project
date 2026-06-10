<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\RackSlot;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $wh1 = Warehouse::where('code', 'WH001')->first();
        if (!$wh1) return;

        // Create inventory snapshot from slot stocks
        $slotStocks = \App\Models\SlotStock::with('slot')->where('is_current', true)->get();

        $count = 0;
        foreach ($slotStocks as $stock) {
            $slotCode = $stock->slot->slot_code;
            $warehouseId = $wh1->id;

            Inventory::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $stock->product_id,
                'rack_slot_id' => $stock->slot_id,
                'batch_number' => $stock->batch_id ? \App\Models\ProductBatch::find($stock->batch_id)?->batch_number : null,
                'quantity' => $stock->quantity,
                'reserved_quantity' => 0,
                'available_quantity' => $stock->quantity,
                'unit_cost' => $stock->unit_cost,
            ]);
            $count++;
        }

        $this->command->info("Inventory records seeded: {$count} records");
    }
}
