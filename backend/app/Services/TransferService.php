<?php

namespace App\Services;

use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class TransferService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Execute a transfer (move stock from source to destination).
     *
     * @param Transfer $transfer
     * @param int $userId
     * @return Transfer
     * @throws \Exception
     */
    public function execute(Transfer $transfer, int $userId): Transfer
    {
        if ($transfer->status !== 'approved') {
            throw new \Exception('Only approved transfers can be executed.');
        }

        DB::beginTransaction();
        try {
            $transfer->update([
                'status' => 'executed',
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            $transfer->loadMissing('items');

            foreach ($transfer->items as $item) {
                $this->inventoryService->transferStock(
                    $item->product_id,
                    $transfer->source_warehouse_id,
                    $transfer->dest_warehouse_id,
                    $item->quantity,
                    $userId,
                    [
                        'reference_type' => 'App\Models\Transfer',
                        'reference_id' => $transfer->id,
                        'reference_number' => $transfer->transfer_number,
                        'notes' => 'Transfer execution ' . $transfer->transfer_number,
                    ]
                );
            }

            DB::commit();
            return $transfer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
