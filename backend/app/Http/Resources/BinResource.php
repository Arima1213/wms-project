<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rack_id' => $this->rack_id,
            'code' => $this->code,
            'level' => $this->level,
            'position' => $this->position,
            'bin_type' => $this->bin_type,
            'max_weight' => $this->max_weight,
            'max_volume' => $this->max_volume,
            'is_active' => $this->is_active,
            'rack' => $this->whenLoaded('rack', fn() => [
                'id' => $this->rack->id,
                'code' => $this->rack->code,
                'zone' => $this->rack->relationLoaded('zone') && $this->rack->zone ? [
                    'id' => $this->rack->zone->id,
                    'name' => $this->rack->zone->name,
                    'warehouse' => $this->rack->zone->relationLoaded('warehouse') && $this->rack->zone->warehouse ? [
                        'id' => $this->rack->zone->warehouse->id,
                        'name' => $this->rack->zone->warehouse->name,
                    ] : null,
                ] : null,
            ]),
            'stocks_count' => $this->whenCounted('stocks'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
