<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_opname_id' => $this->stock_opname_id,
            'product_id' => $this->product_id,
            'slot_id' => $this->slot_id,
            'system_qty' => $this->system_qty,
            'counted_qty' => $this->counted_qty,
            'variance' => $this->variance,
            'variance_status' => $this->variance_status,
            'counted_by' => $this->counted_by,
            'counted_at' => $this->counted_at,
            'notes' => $this->notes,
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'barcode' => $this->product->barcode,
            ]),
            'slot' => $this->whenLoaded('slot', fn() => [
                'id' => $this->slot->id,
                'slot_code' => $this->slot->slot_code,
            ]),
        ];
    }
}
