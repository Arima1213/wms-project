<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RackSlot;
use App\Models\SlotStock;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    public function run(): void
    {
        $wh1 = Warehouse::where('code', 'WH001')->first();
        $admin = User::where('email', 'admin@wms.local')->first();
        $operator = User::where('email', 'ahmad@wms.local')->first();

        if (!$wh1 || !$admin) return;

        // ─── STOCK OPNAME 1: Approved (full warehouse count) ──────────
        $op1 = StockOpname::create([
            'opname_number' => 'SO-20260601-001',
            'warehouse_id' => $wh1->id,
            'type' => 'full',
            'status' => 'approved',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-02',
            'notes' => 'Monthly full stock opname — all matched',
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(8),
        ]);

        // Add some opname items from slot stocks (all matching)
        $slotStocks = SlotStock::with('product', 'slot')
            ->where('is_current', true)
            ->whereHas('slot', function ($q) {
                $q->where('slot_code', 'like', 'A-01-L1-S%');
            })
            ->get();

        foreach ($slotStocks as $stock) {
            StockOpnameItem::create([
                'stock_opname_id' => $op1->id,
                'product_id' => $stock->product_id,
                'slot_id' => $stock->slot_id,
                'system_qty' => $stock->quantity,
                'counted_qty' => $stock->quantity,
                'variance' => 0,
                'variance_status' => 'match',
                'counted_by' => $operator?->id,
                'counted_at' => now()->subDays(8),
            ]);
        }

        // ─── STOCK OPNAME 2: In Progress (cycle count Zone B) ─────────
        $op2 = StockOpname::create([
            'opname_number' => 'SO-20260610-002',
            'warehouse_id' => $wh1->id,
            'type' => 'cycle',
            'status' => 'in_progress',
            'start_date' => '2026-06-10',
            'notes' => 'Cycle count Zone B — slow moving items',
            'created_by' => $operator?->id,
        ]);

        $slowStocks = SlotStock::with('product', 'slot')
            ->where('is_current', true)
            ->whereHas('slot', function ($q) {
                $q->where('slot_code', 'like', 'B-01-L1-S%');
            })
            ->get();

        foreach ($slowStocks as $stock) {
            // Simulate some variance on one item
            $counted = $stock->quantity;
            $variance = 0;
            $varianceStatus = 'match';

            if ($stock->product->sku === 'FMCG-003') {
                $counted = $stock->quantity - 5; // 5 units short
                $variance = -5;
                $varianceStatus = 'short';
            }

            StockOpnameItem::create([
                'stock_opname_id' => $op2->id,
                'product_id' => $stock->product_id,
                'slot_id' => $stock->slot_id,
                'system_qty' => $stock->quantity,
                'counted_qty' => $counted,
                'variance' => $variance,
                'variance_status' => $varianceStatus,
                'counted_by' => $operator?->id,
                'counted_at' => now(),
            ]);
        }

        $this->command->info('Stock opnames seeded: 2 opnames (1 approved, 1 in progress)');
    }
}
