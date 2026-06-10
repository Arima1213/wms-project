<?php

namespace App\Services;

use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Product;
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
        if (in_array($inbound->status, ['received', 'cancelled'])) {
            throw new \Exception('Inbound already received or cancelled.');
        }

        DB::beginTransaction();
        try {
            $inbound->loadMissing('items');

            if ($items === null) {
                // Full receive
                foreach ($inbound->items as $item) {
                    $item->update([
                        'received_qty' => $item->expected_qty,
                        'received_at' => now(),
                    ]);

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
                            'uom_id' => $item->product?->unit_id,
                        ]
                    );
                }

                $inbound->update([
                    'status' => 'received',
                    'received_date' => now(),
                ]);
            } else {
                // Partial receive
                $wasPending = $inbound->status === 'pending';

                foreach ($items as $itemData) {
                    $item = InboundItem::find($itemData['id']);
                    if (!$item) {
                        continue;
                    }

                    if ($itemData['received_qty'] > $item->expected_qty) {
                        throw new \Exception("Received quantity cannot exceed expected quantity for item ID {$item->id}");
                    }

                    $additional_qty = $itemData['received_qty'] - $item->received_qty;

                    $item->update([
                        'received_qty' => $itemData['received_qty'],
                        'received_at' => now(),
                    ]);

                    if ($additional_qty > 0) {
                        $this->inventoryService->receiveStock(
                            $item->product_id,
                            $inbound->warehouse_id,
                            $additional_qty,
                            $userId,
                            [
                                'batch_number' => $item->batch_number,
                                'expiry_date' => $item->expiry_date,
                                'reference_type' => 'App\\Models\\Inbound',
                                'reference_id' => $inbound->id,
                                'reference_number' => $inbound->inbound_number,
                                'notes' => 'Received from inbound ' . $inbound->inbound_number,
                                'uom_id' => $item->product?->unit_id,
                            ]
                        );
                    }
                }

                $inbound->load('items');

                $everyItemFull = true;
                $anyItemPartial = false;

                foreach ($inbound->items as $item) {
                    if ($item->received_qty < $item->expected_qty) {
                        $everyItemFull = false;
                    }
                    if ($item->received_qty > 0) {
                        $anyItemPartial = true;
                    }
                }

                $newStatus = $inbound->status;
                if ($everyItemFull) {
                    $newStatus = 'received';
                } elseif ($anyItemPartial) {
                    $newStatus = 'partial';
                }

                $updateData = ['status' => $newStatus];
                if ($wasPending && $newStatus !== 'pending') {
                    $updateData['received_date'] = now();
                }

                $inbound->update($updateData);
            }

            DB::commit();
            return $inbound->fresh()->load('items');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
