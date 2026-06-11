<?php

namespace App\Services;

use App\Models\Returns as ReturnModel;
use App\Models\ReturnItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReturnService
{
    protected InventoryService $inventoryService;
    protected DocumentSequenceService $documentSequence;

    public function __construct(InventoryService $inventoryService, DocumentSequenceService $documentSequence)
    {
        $this->inventoryService = $inventoryService;
        $this->documentSequence = $documentSequence;
    }

    public function create(array $data, int $userId): ReturnModel
    {
        DB::beginTransaction();
        try {
            $return = ReturnModel::create([
                'return_number' => $this->documentSequence->generate('RET'),
                'warehouse_id' => $data['warehouse_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'type' => $data['type'],
                'reason' => $data['reason'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'return_date' => $data['return_date'] ?? now(),
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $return->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'condition' => $item['condition'] ?? 'good',
                    'resolution' => $item['resolution'] ?? 'restock',
                    'refund_amount' => $item['refund_amount'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();
            return $return->loadMissing(['items.product', 'warehouse', 'customer', 'supplier', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approve(ReturnModel $return, int $userId): ReturnModel
    {
        if ($return->status !== 'pending') {
            throw new \Exception('Only pending returns can be approved.');
        }

        DB::beginTransaction();
        try {
            $return->update([
                'status' => 'approved',
                'processed_by' => $userId,
                'processed_date' => now(),
            ]);

            DB::commit();
            return $return->fresh()->loadMissing(['items.product', 'warehouse']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function process(ReturnModel $return, int $userId): ReturnModel
    {
        if (!in_array($return->status, ['approved', 'pending'])) {
            throw new \Exception('Return must be approved or pending to process.');
        }

        DB::beginTransaction();
        try {
            $return->loadMissing('items.product');

            foreach ($return->items as $item) {
                $qty = $item->quantity;

                // Restock: return goods to inventory
                if ($item->resolution === 'restock') {
                    $this->inventoryService->receiveStock(
                        $item->product_id,
                        $return->warehouse_id,
                        $qty,
                        $userId,
                        "Return: {$return->return_number}"
                    );
                }
            }

            $return->update([
                'status' => 'processed',
                'processed_by' => $userId,
                'processed_date' => now(),
            ]);

            DB::commit();
            return $return->fresh()->loadMissing(['items.product', 'warehouse']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reject(ReturnModel $return, int $userId, ?string $reason = null): ReturnModel
    {
        if (!in_array($return->status, ['pending', 'approved'])) {
            throw new \Exception('Only pending/approved returns can be rejected.');
        }

        $return->update([
            'status' => 'rejected',
            'processed_by' => $userId,
            'processed_date' => now(),
            'notes' => $reason ? $return->notes . "\nRejection reason: " . $reason : $return->notes,
        ]);

        return $return->fresh();
    }

    public function cancel(ReturnModel $return, int $userId): ReturnModel
    {
        if (in_array($return->status, ['processed', 'cancelled'])) {
            throw new \Exception('Return already processed or cancelled.');
        }

        $return->update([
            'status' => 'cancelled',
            'processed_by' => $userId,
        ]);

        return $return->fresh();
    }
}
