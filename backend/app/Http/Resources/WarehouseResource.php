<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'capacity_m2' => $this->capacity_m2,
            'warehouse_type' => $this->warehouse_type,
            'pic_name' => $this->pic_name,
            'pic_phone' => $this->pic_phone,
            'pic_email' => $this->pic_email,
            'operating_hours' => $this->operating_hours,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'zones' => ZoneResource::collection($this->whenLoaded('zones')),
            'planogram' => new PlanogramResource($this->whenLoaded('planogram')),
        ];
    }
}
