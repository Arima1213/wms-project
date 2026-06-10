<?php

namespace App\Services;

use App\Models\Outbound;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OutboundService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process shipping of an Outbound document.
     *
     * @param Outbound $outbound
     * @param int $userId
     * @return Outbound
     * @throws \Exception
     */
    public function ship(Outbound $outbound, int $userId): Outbound
    {
        if (!in_array($outbound->status, ['pending', 'picking'])) {
            throw new \Exception('Cannot ship this outbound. Invalid status.');
        }

        DB::beginTransaction();
        try {
            $outbound->update([
                'status' => 'shipped',
                'shipped_date' => now(),
            ]);

            // Ensure items are loaded
            $outbound->loadMissing('items');

            foreach ($outbound->items as $item) {
                $item->update(['picked_qty' => $item->ordered_qty, 'shipped_qty' => $item->ordered_qty, 'status' => 'shipped']);

                $this->inventoryService->issueStock(
                    $item->product_id,
                    $outbound->warehouse_id,
                    $item->ordered_qty,
                    $userId,
                    [
                        'reference_type' => 'App\\Models\\Outbound',
                        'reference_id' => $outbound->id,
                        'reference_number' => $outbound->outbound_number,
                        'notes' => 'Shipped for outbound ' . $outbound->outbound_number,
                        'uom_id' => $item->product?->unit_id,
                    ]
                );
            }

            DB::commit();
            return $outbound;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
