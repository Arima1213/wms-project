<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutboundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'outbound_number' => $this->outbound_number,
            'warehouse_id' => $this->warehouse_id,
            'type' => $this->type,
            'status' => $this->status,
            'order_date' => $this->order_date,
            'shipped_date' => $this->shipped_date,
            'delivered_date' => $this->delivered_date,
            'reference_number' => $this->reference_number,
            'destination_name' => $this->destination_name,
            'destination_address' => $this->destination_address,
            'shipping_method' => $this->shipping_method,
            'tracking_number' => $this->tracking_number,
            'shipping_cost' => $this->shipping_cost,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'warehouse' => $this->whenLoaded('warehouse', fn() => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'items' => OutboundItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
