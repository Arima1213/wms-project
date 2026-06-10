<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutboundItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'outbound_id' => $this->outbound_id,
            'product_id' => $this->product_id,
            'ordered_qty' => $this->ordered_qty,
            'picked_qty' => $this->picked_qty,
            'shipped_qty' => $this->shipped_qty,
            'unit_price' => $this->unit_price,
            'source_slot_id' => $this->source_slot_id,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'barcode' => $this->product->barcode,
            ]),
        ];
    }
}
