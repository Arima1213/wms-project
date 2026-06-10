<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_id' => $this->transfer_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'received_qty' => $this->received_qty,
            'source_slot_id' => $this->source_slot_id,
            'dest_slot_id' => $this->dest_slot_id,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date,
            'notes' => $this->notes,
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
            ]),
        ];
    }
}
