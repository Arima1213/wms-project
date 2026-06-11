<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_id' => $this->return_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'condition' => $this->condition,
            'resolution' => $this->resolution,
            'refund_amount' => $this->refund_amount,
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
