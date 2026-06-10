<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\Product;
use App\Models\RackSlot;
use App\Models\SlotStock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class OutboundSeeder extends Seeder
{
    public function run(): void
    {
        $wh1 = Warehouse::where('code', 'WH001')->first();
        $admin = User::where('email', 'admin@wms.local')->first();
        $manager = User::where('email', 'budi@wms.local')->first();
        $operator = User::where('email', 'siti@wms.local')->first();
        $cust1 = Customer::where('code', 'CUST-001')->first();
        $cust3 = Customer::where('code', 'CUST-003')->first();

        if (!$wh1 || !$admin) return;

        // ─── OUTBOUND 1: Delivered (retail order) ─────────────────────────
        $out1 = Outbound::create([
            'outbound_number' => 'OUT-20260610-0001',
            'warehouse_id' => $wh1->id,
            'customer_id' => $cust1?->id,
            'type' => 'sales',
            'status' => 'delivered',
            'order_date' => '2026-06-08',
            'shipped_date' => '2026-06-09',
            'delivered_date' => '2026-06-10',
            'reference_number' => 'SO-20260608-001',
            'destination_name' => 'PT Retail Maju Jaya',
            'destination_address' => 'Jl. Sudirman No. 15, Jakarta',
            'shipping_method' => 'SiCepat',
            'tracking_number' => 'SCP-20260609-001',
            'shipping_cost' => 50000,
            'total_amount' => 8250000,
            'notes' => 'Routine stock replenishment',
            'created_by' => $admin->id,
            'approved_by' => $manager?->id,
            'approved_at' => now()->subDays(2),
        ]);

        $this->createOutboundItem($out1, 'ELEC-001', 50, 50, 50, 25000, 'A-01-L1-S1');
        $this->createOutboundItem($out1, 'ELEC-002', 30, 30, 30, 55000, 'A-01-L1-S2');

        // ─── OUTBOUND 2: Shipped (distributor order) ────────────────────
        $out2 = Outbound::create([
            'outbound_number' => 'OUT-20260610-0002',
            'warehouse_id' => $wh1->id,
            'customer_id' => $cust3?->id,
            'type' => 'sales',
            'status' => 'shipped',
            'order_date' => '2026-06-09',
            'shipped_date' => '2026-06-10',
            'reference_number' => 'SO-20260609-002',
            'destination_name' => 'PT Distribusi Lancar',
            'destination_address' => 'Jl. Asia Afrika No. 8, Bandung',
            'shipping_method' => 'JNE',
            'tracking_number' => 'JNE-20260610-001',
            'shipping_cost' => 75000,
            'total_amount' => 15200000,
            'notes' => 'Bulk packaging order',
            'created_by' => $operator?->id,
            'approved_by' => $manager?->id,
            'approved_at' => now()->subDay(),
        ]);

        $this->createOutboundItem($out2, 'PACK-001', 200, 200, 200, 7500, 'A-01-L1-S5');
        $this->createOutboundItem($out2, 'PACK-003', 10, 10, 10, 150000, 'A-02-L1-S2');

        // ─── OUTBOUND 3: Pending (new order, not yet processed) ────────────
        $out3 = Outbound::create([
            'outbound_number' => 'OUT-20260610-0003',
            'warehouse_id' => $wh1->id,
            'customer_id' => $cust1?->id,
            'type' => 'sales',
            'status' => 'pending',
            'order_date' => '2026-06-10',
            'reference_number' => 'SO-20260610-003',
            'destination_name' => 'PT Retail Maju Jaya',
            'destination_address' => 'Jl. Sudirman No. 15, Jakarta',
            'shipping_method' => 'GoSend',
            'total_amount' => 4800000,
            'notes' => 'Urgent — same day delivery requested',
            'created_by' => $operator?->id,
        ]);

        $this->createOutboundItem($out3, 'ELEC-003', 15, 0, 0, 185000, null);
        $this->createOutboundItem($out3, 'FMCG-001', 10, 0, 0, 372000, null);

        $this->command->info('Outbound documents seeded: 3 outbounds (1 delivered, 1 shipped, 1 pending)');
    }

    private function createOutboundItem(
        Outbound $outbound, string $sku, float $ordered, ?float $picked,
        ?float $shipped, float $price, ?string $slotCode
    ): void {
        $product = Product::where('sku', $sku)->first();
        if (!$product) return;

        $slot = $slotCode ? RackSlot::where('slot_code', $slotCode)->first() : null;

        $status = 'pending';
        if ($shipped !== null && $shipped > 0) {
            $status = 'shipped';
        } elseif ($picked !== null && $picked > 0) {
            $status = 'picked';
        }

        OutboundItem::create([
            'outbound_id' => $outbound->id,
            'product_id' => $product->id,
            'ordered_qty' => $ordered,
            'picked_qty' => $picked ?? 0,
            'shipped_qty' => $shipped ?? 0,
            'unit_price' => $price,
            'source_slot_id' => $slot?->id,
            'status' => $status,
        ]);
    }
}
