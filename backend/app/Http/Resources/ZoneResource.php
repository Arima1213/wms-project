<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'code' => $this->code,
            'name' => $this->name,
            'zone_type' => $this->zone_type,
            'temperature_range' => $this->temperature_range,
            'humidity_range' => $this->humidity_range,
            'allowed_product_types' => $this->allowed_product_types,
            'description' => $this->description,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'racks_count' => $this->whenCounted('racks'),
        ];
    }
}
