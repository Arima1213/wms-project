<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InboundItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inbound_id' => $this->inbound_id,
            'product_id' => $this->product_id,
            'expected_qty' => $this->expected_qty,
            'received_qty' => $this->received_qty,
            'accepted_qty' => $this->accepted_qty,
            'rejected_qty' => $this->rejected_qty,
            'unit_cost' => $this->unit_cost,
            'batch_number' => $this->batch_number,
            'manufacture_date' => $this->manufacture_date,
            'expiry_date' => $this->expiry_date,
            'dest_slot_id' => $this->dest_slot_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'received_at' => $this->received_at,
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'barcode' => $this->product->barcode,
            ]),
            'dest_slot' => $this->whenLoaded('destSlot', fn() => [
                'id' => $this->destSlot->id,
                'slot_code' => $this->destSlot->slot_code,
            ]),
        ];
    }
}
