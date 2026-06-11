<?php

namespace App\Services;

use App\Models\Returns;
use App\Models\ReturnItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    protected InventoryService $inventoryService;
    protected DocumentSequenceService $documentSequence;
    protected NotificationService $notificationService;

    public function __construct(
        InventoryService $inventoryService,
        DocumentSequenceService $documentSequence,
        NotificationService $notificationService
    ) {
        $this->inventoryService = $inventoryService;
        $this->documentSequence = $documentSequence;
        $this->notificationService = $notificationService;
    }

    public function create(array $data, int $userId): Returns
    {
        DB::beginTransaction();
        try {
            $returnNumber = $this->documentSequence->generate('RET', $data['warehouse_id']);

            $return = Returns::create([
                'return_number' => $returnNumber,
                'warehouse_id' => $data['warehouse_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'type' => $data['type'],
                'reason' => $data['reason'] ?? null,
                'status' => 'draft',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'return_date' => $data['return_date'] ?? now(),
                'created_by' => $userId,
            ]);

            $totalRefund = 0;
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $lineRefund = ($item['refund_amount'] ?? 0) > 0 ? $item['refund_amount'] : 0;

                ReturnItem::create([
                    'return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'condition' => $item['condition'] ?? 'good',
                    'resolution' => $item['resolution'] ?? 'restock',
                    'refund_amount' => $lineRefund,
                    'notes' => $item['notes'] ?? null,
                ]);

                $totalRefund += $lineRefund;
            }

            $return->update(['refund_amount' => $totalRefund]);

            $this->notificationService->createNotification(
                'return_created',
                "Return {$returnNumber} telah dibuat",
                $return->id,
                Returns::class,
                $userId,
                $data['warehouse_id']
            );

            DB::commit();
            return $return->fresh(['items.product', 'warehouse', 'customer', 'supplier', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approve(Returns $return, int $userId): Returns
    {
        if ($return->status !== 'pending') {
            throw new \Exception('Only pending returns can be approved.');
        }

        DB::beginTransaction();
        try {
            $return->update([
                'status' => 'approved',
                'processed_by' => $userId,
            ]);

            $this->notificationService->createNotification(
                'return_approved',
                "Return {$return->return_number} telah disetujui",
                $return->id,
                Returns::class,
                $userId,
                $return->warehouse_id
            );

            DB::commit();
            return $return->fresh(['items.product', 'warehouse', 'customer', 'supplier', 'creator', 'processor']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function process(Returns $return, int $userId): Returns
    {
        if (!in_array($return->status, ['approved', 'pending'])) {
            throw new \Exception('Return must be approved or pending to process.');
        }

        DB::beginTransaction();
        try {
            $return->loadMissing('items.product');

            foreach ($return->items as $item) {
                if ($item->resolution === 'restock') {
                    $this->inventoryService->receiveStock(
                        $item->product_id,
                        $return->warehouse_id,
                        $item->quantity,
                        $userId,
                        "Return {$return->return_number}",
                        null,
                        $item->condition
                    );
                }
            }

            $return->update([
                'status' => 'processed',
                'processed_date' => now(),
                'processed_by' => $userId,
            ]);

            $this->notificationService->createNotification(
                'return_processed',
                "Return {$return->return_number} telah diproses — stok dikembalikan",
                $return->id,
                Returns::class,
                $userId,
                $return->warehouse_id
            );

            DB::commit();
            return $return->fresh(['items.product', 'warehouse', 'customer', 'supplier', 'creator', 'processor']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reject(Returns $return, int $userId, ?string $reason = null): Returns
    {
        if (!in_array($return->status, ['pending', 'approved'])) {
            throw new \Exception('Only pending or approved returns can be rejected.');
        }

        DB::beginTransaction();
        try {
            $return->update([
                'status' => 'rejected',
                'notes' => $reason ? ($return->notes . "\nRejection reason: " . $reason) : $return->notes,
                'processed_by' => $userId,
                'processed_date' => now(),
            ]);

            DB::commit();
            return $return->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cancel(Returns $return, int $userId): Returns
    {
        if (in_array($return->status, ['processed', 'cancelled'])) {
            throw new \Exception('Return cannot be cancelled in its current state.');
        }

        DB::beginTransaction();
        try {
            $return->update([
                'status' => 'cancelled',
                'processed_by' => $userId,
            ]);

            DB::commit();
            return $return->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
