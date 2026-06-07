<?php

namespace App\Services;

use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Approve a stock opname and adjust inventory accordingly.
     *
     * @param StockOpname $opname
     * @param int $userId
     * @return StockOpname
     * @throws \Exception
     */
    public function approve(StockOpname $opname, int $userId): StockOpname
    {
        if ($opname->status !== 'submitted') {
            throw new \Exception('Only submitted stock opnames can be approved.');
        }

        DB::beginTransaction();
        try {
            $opname->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            $opname->loadMissing('items');

            foreach ($opname->items as $item) {
                // variance = actual_qty - system_qty
                // e.g. system=10, actual=12 => variance=2 (we need to ADJ+ 2)
                // e.g. system=10, actual=8 => variance=-2 (we need to ADJ- 2)
                if ($item->variance != 0) {
                    $this->inventoryService->adjustStock(
                        $item->product_id,
                        $opname->warehouse_id,
                        $item->variance,
                        $userId,
                        [
                            'reference_type' => 'App\Models\StockOpname',
                            'reference_id' => $opname->id,
                            'reference_number' => $opname->opname_number,
                            'notes' => 'Stock opname adjustment ' . $opname->opname_number,
                        ]
                    );
                }
            }

            DB::commit();
            return $opname;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
