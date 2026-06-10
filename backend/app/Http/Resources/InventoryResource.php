<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'rack_slot_id' => $this->rack_slot_id,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'unit_cost' => $this->unit_cost,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'barcode' => $this->product->barcode,
                'category_id' => $this->product->category_id,
            ]),
            'warehouse' => $this->whenLoaded('warehouse', fn() => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),
            'rack_slot' => $this->whenLoaded('rackSlot', fn() => [
                'id' => $this->rackSlot->id,
                'slot_code' => $this->rackSlot->slot_code,
            ]),
        ];
    }
}
