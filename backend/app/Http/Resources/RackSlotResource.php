<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RackSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rack_level_id' => $this->rack_level_id,
            'slot_code' => $this->slot_code,
            'slot_number' => $this->slot_number,
            'max_weight_kg' => $this->max_weight_kg,
            'max_volume_cm3' => $this->max_volume_cm3,
            'slot_type' => $this->slot_type,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'is_reserved' => (bool) $this->is_reserved,
            'reserved_until' => $this->reserved_until,
            'reserved_for' => $this->reserved_for,
            'fixed_product_id' => $this->fixed_product_id,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'level' => $this->whenLoaded('level', fn() => [
                'id' => $this->level->id,
                'level_number' => $this->level->level_number,
            ]),
            'fixed_product' => $this->whenLoaded('fixedProduct', fn() => [
                'id' => $this->fixedProduct->id,
                'sku' => $this->fixedProduct->sku,
                'name' => $this->fixedProduct->name,
            ]),
        ];
    }
}
