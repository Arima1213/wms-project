<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zone_id' => $this->zone_id,
            'code' => $this->code,
            'name' => $this->name,
            'canvas_x' => $this->canvas_x,
            'canvas_y' => $this->canvas_y,
            'width_cm' => $this->width_cm,
            'depth_cm' => $this->depth_cm,
            'height_cm' => $this->height_cm,
            'orientation' => $this->orientation,
            'max_weight_kg' => $this->max_weight_kg,
            'metadata' => $this->metadata,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'levels_count' => $this->whenCounted('levels'),
            'slots_count' => $this->whenCounted('slots'),
            'zone' => $this->whenLoaded('zone', fn() => [
                'id' => $this->zone->id,
                'code' => $this->zone->code,
                'name' => $this->zone->name,
            ]),
            'levels' => RackLevelResource::collection($this->whenLoaded('levels')),
        ];
    }
}
