<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RackSlot;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    public function run(): void
    {
        $wh1 = Warehouse::where('code', 'WH001')->first();
        $wh2 = Warehouse::where('code', 'WH002')->first();
        $manager = User::where('email', 'budi@wms.local')->first();
        $operator = User::where('email', 'ahmad@wms.local')->first();

        if (!$wh1 || !$wh2 || !$manager) return;

        // ─── TRANSFER 1: Completed (Jakarta → Surabaya) ────────────────
        $trf1 = Transfer::create([
            'transfer_number' => 'TRF-20260610-0001',
            'source_warehouse_id' => $wh1->id,
            'dest_warehouse_id' => $wh2->id,
            'status' => 'completed',
            'reason' => 'Stock redistribution — overstock at WH001',
            'notes' => 'Regular inter-warehouse transfer',
            'expected_date' => '2026-06-09',
            'completed_date' => '2026-06-10',
            'created_by' => $manager->id,
            'approved_by' => $manager->id,
            'approved_at' => now()->subDays(2),
        ]);

        $this->createTransferItem($trf1, 'ELEC-001', 100, 100, 'A-01-L1-S1', null);
        $this->createTransferItem($trf1, 'PACK-001', 200, 200, 'A-01-L1-S5', null);

        // ─── TRANSFER 2: In Transit (Jakarta → Surabaya) ──────────────
        $trf2 = Transfer::create([
            'transfer_number' => 'TRF-20260610-0002',
            'source_warehouse_id' => $wh1->id,
            'dest_warehouse_id' => $wh2->id,
            'status' => 'in_transit',
            'reason' => 'FMCG stock transfer for Surabaya distribution',
            'expected_date' => '2026-06-12',
            'created_by' => $operator->id,
            'approved_by' => $manager->id,
            'approved_at' => now(),
        ]);

        $this->createTransferItem($trf2, 'FMCG-001', 30, 0, 'A-02-L1-S4', null);
        $this->createTransferItem($trf2, 'FMCG-002', 50, 0, 'B-01-L1-S1', null);

        $this->command->info('Transfers seeded: 2 transfers (1 completed, 1 in transit)');
    }

    private function createTransferItem(
        Transfer $transfer, string $sku, float $qty, float $received,
        ?string $sourceSlotCode, ?string $destSlotCode
    ): void {
        $product = Product::where('sku', $sku)->first();
        if (!$product) return;

        $sourceSlot = $sourceSlotCode ? RackSlot::where('slot_code', $sourceSlotCode)->first() : null;
        $destSlot = $destSlotCode ? RackSlot::where('slot_code', $destSlotCode)->first() : null;

        TransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'received_qty' => $received,
            'source_slot_id' => $sourceSlot?->id,
            'dest_slot_id' => $destSlot?->id,
        ]);
    }
}
