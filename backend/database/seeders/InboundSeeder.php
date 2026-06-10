<?php

namespace Database\Seeders;

use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Product;
use App\Models\RackSlot;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InboundSeeder extends Seeder
{
    public function run(): void
    {
        $wh1 = Warehouse::where('code', 'WH001')->first();
        $admin = User::where('email', 'admin@wms.local')->first();
        $manager = User::where('email', 'budi@wms.local')->first();
        $supplier1 = Supplier::where('code', 'SUP-001')->first();
        $supplier2 = Supplier::where('code', 'SUP-002')->first();
        $supplier4 = Supplier::where('code', 'SUP-004')->first();

        if (!$wh1 || !$admin) return;

        // ─── INBOUND 1: Received (electronics from PT Elektronika Nusantara) ──
        $inb1 = Inbound::create([
            'inbound_number' => 'INB-20260610-0001',
            'warehouse_id' => $wh1->id,
            'supplier_id' => $supplier1?->id,
            'source_type' => 'purchase',
            'source_reference' => 'PO-20260601-001',
            'status' => 'received',
            'expected_date' => '2026-06-08',
            'received_date' => '2026-06-10',
            'notes' => 'Receiving USB cables and adapters',
            'created_by' => $admin->id,
            'approved_by' => $manager?->id,
            'approved_at' => now()->subDays(2),
        ]);

        $this->createInboundItem($inb1, 'ELEC-001', 300, 300, 300, 0, 15000, 'A-01-L1-S1');
        $this->createInboundItem($inb1, 'ELEC-002', 150, 150, 148, 2, 35000, 'A-01-L1-S2');

        // ─── INBOUND 2: Partial (packaging from CV Packaging Jaya) ─────────
        $inb2 = Inbound::create([
            'inbound_number' => 'INB-20260610-0002',
            'warehouse_id' => $wh1->id,
            'supplier_id' => $supplier2?->id,
            'source_type' => 'purchase',
            'source_reference' => 'PO-20260605-002',
            'status' => 'partial',
            'expected_date' => '2026-06-10',
            'received_date' => '2026-06-10',
            'notes' => 'Partially received — bubble wrap still in transit',
            'created_by' => $admin->id,
        ]);

        $this->createInboundItem($inb2, 'PACK-001', 1000, 500, 500, 0, 5000, 'A-01-L1-S5');
        $this->createInboundItem($inb2, 'PACK-002', 50, 0, 0, 0, 85000, null);

        // ─── INBOUND 3: Pending (FMCG from PT Segar Makmur) ────────────────
        $inb3 = Inbound::create([
            'inbound_number' => 'INB-20260610-0003',
            'warehouse_id' => $wh1->id,
            'supplier_id' => $supplier4?->id,
            'source_type' => 'purchase',
            'source_reference' => 'PO-20260608-003',
            'status' => 'pending',
            'expected_date' => '2026-06-12',
            'notes' => 'Awaiting arrival — milk and noodles',
            'created_by' => $admin->id,
        ]);

        $this->createInboundItem($inb3, 'FMCG-001', 100, 0, 0, 0, 15500, null, true);
        $this->createInboundItem($inb3, 'FMCG-002', 200, 0, 0, 0, 3500, null, true);

        $this->command->info('Inbound documents seeded: 3 inbounds (1 received, 1 partial, 1 pending)');
    }

    private function createInboundItem(
        Inbound $inbound, string $sku, float $expected, ?float $received,
        ?float $accepted, ?float $rejected, float $cost, ?string $slotCode,
        bool $trackBatch = false
    ): void {
        $product = Product::where('sku', $sku)->first();
        if (!$product) return;

        $slot = $slotCode ? RackSlot::where('slot_code', $slotCode)->first() : null;

        $status = 'pending';
        $receivedAt = null;
        if ($received !== null && $received > 0) {
            $status = ($received < $expected) ? 'partial' : 'received';
            $receivedAt = now();
        }

        InboundItem::create([
            'inbound_id' => $inbound->id,
            'product_id' => $product->id,
            'expected_qty' => $expected,
            'received_qty' => $received ?? 0,
            'accepted_qty' => $accepted ?? 0,
            'rejected_qty' => $rejected ?? 0,
            'unit_cost' => $cost,
            'dest_slot_id' => $slot?->id,
            'status' => $status,
            'received_at' => $receivedAt,
        ]);
    }
}
