<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'return_number' => $this->return_number,
            'warehouse_id' => $this->warehouse_id,
            'type' => $this->type,
            'reason' => $this->reason,
            'status' => $this->status,
            'notes' => $this->notes,
            'refund_amount' => $this->refund_amount,
            'return_date' => $this->return_date,
            'processed_date' => $this->processed_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'warehouse' => $this->whenLoaded('warehouse', fn() => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),
            'customer' => $this->whenLoaded('customer', fn() => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn() => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'items' => ReturnItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
