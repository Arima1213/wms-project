<?php

namespace App\Services;

use App\Models\Inbound;
use Illuminate\Support\Facades\DB;

class InboundService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process receiving of an Inbound document.
     *
     * @param Inbound $inbound
     * @param int $userId
     * @return Inbound
     * @throws \Exception
     */
    public function receive(Inbound $inbound, int $userId): Inbound
    {
        if ($inbound->status !== 'pending') {
            throw new \Exception('Inbound already received or cancelled.');
        }

        DB::beginTransaction();
        try {
            $inbound->update([
                'status' => 'received',
                'received_date' => now(),
            ]);

            // Ensure items are loaded
            $inbound->loadMissing('items');

            foreach ($inbound->items as $item) {
                $item->update(['received_qty' => $item->expected_qty, 'received_at' => now()]);

                $this->inventoryService->receiveStock(
                    $item->product_id,
                    $inbound->warehouse_id,
                    $item->expected_qty,
                    $userId,
                    [
                        'batch_number' => $item->batch_number,
                        'expiry_date' => $item->expiry_date,
                        'reference_type' => 'App\Models\Inbound',
                        'reference_id' => $inbound->id,
                        'reference_number' => $inbound->inbound_number,
                        'notes' => 'Received from inbound ' . $inbound->inbound_number,
                    ]
                );
            }

            DB::commit();
            return $inbound;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
