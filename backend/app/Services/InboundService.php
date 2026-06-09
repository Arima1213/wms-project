<?php

namespace App\Services;

use App\Models\Inbound;
use App\Models\InboundItem;
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
     * @param array|null $items Array of ['id' => int, 'received_qty' => float] for partial receive. Null for full receive.
     * @return Inbound
     * @throws \Exception
     */
    public function receive(Inbound $inbound, int $userId, ?array $items = null): Inbound
    {
        if ($inbound->status !== 'pending') {
            throw new \Exception('Inbound already received or cancelled.');
        }

        DB::beginTransaction();
        try {
            $inbound->loadMissing('items');

            if ($items === null) {
                // Full receive — existing behavior
                $this->fullReceive($inbound, $userId);
            } else {
                // Partial receive
                $this->partialReceive($inbound, $userId, $items);
            }

            DB::commit();
            return $inbound->fresh()->load('items');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Full receive: all items received at expected_qty.
     */
    protected function fullReceive(Inbound $inbound, int $userId): void
    {
        $inbound->update([
            'status' => 'received',
            'received_date' => now(),
        ]);

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
                    'reference_type' => 'App\\Models\\Inbound',
                    'reference_id' => $inbound->id,
                    'reference_number' => $inbound->inbound_number,
                    'notes' => 'Received from inbound ' . $inbound->inbound_number,
                ]
            );
        }
    }

    /**
     * Partial receive: only specified items with given received_qty.
     *
     * @param array $items Array of ['id' => int, 'received_qty' => float]
     */
    protected function partialReceive(Inbound $inbound, int $userId, array $items): void
    {
        $specifiedItemIds = collect($items)->pluck('id')->toArray();

        foreach ($items as $input) {
            /** @var InboundItem|null $item */
            $item = $inbound->items->firstWhere('id', $input['id']);

            if (! $item) {
                throw new \Exception("Item ID {$input['id']} not found in this inbound document.");
            }

            $newQty = (float) $input['received_qty'];

            if ($newQty > $item->expected_qty) {
                throw new \Exception(
                    "Received qty ({$newQty}) for item ID {$item->id} cannot exceed expected qty ({$item->expected_qty})."
                );
            }

            if ($newQty < 0) {
                throw new \Exception('Received qty cannot be negative.');
            }

            $additionalQty = $newQty - (float) $item->received_qty;

            if ($additionalQty < 0) {
                throw new \Exception(
                    "Cannot reduce received qty for item ID {$item->id}. Current: {$item->received_qty}, new: {$newQty}."
                );
            }

            // Only update if something actually changed
            if ($additionalQty > 0) {
                $item->update([
                    'received_qty' => $newQty,
                    'received_at' => now(),
                ]);

                $this->inventoryService->receiveStock(
                    $item->product_id,
                    $inbound->warehouse_id,
                    $additionalQty,
                    $userId,
                    [
                        'batch_number' => $item->batch_number,
                        'expiry_date' => $item->expiry_date,
                        'reference_type' => 'App\\Models\\Inbound',
                        'reference_id' => $inbound->id,
                        'reference_number' => $inbound->inbound_number,
                        'notes' => 'Received from inbound ' . $inbound->inbound_number,
                    ]
                );
            }
        }

        // Determine overall inbound status
        $inbound->refresh();
        $inbound->load('items');

        $allFull = $inbound->items->every(fn ($i) => (float) $i->received_qty >= (float) $i->expected_qty);
        $anyReceived = $inbound->items->some(fn ($i) => (float) $i->received_qty > 0);

        if ($allFull) {
            $inbound->update([
                'status' => 'received',
                'received_date' => $inbound->received_date ?? now(),
            ]);
        } elseif ($anyReceived) {
            $inbound->update([
                'status' => 'partial',
                'received_date' => $inbound->received_date ?? now(),
            ]);
        }
        // If none received, stay 'pending' (no update needed)
    }
}
