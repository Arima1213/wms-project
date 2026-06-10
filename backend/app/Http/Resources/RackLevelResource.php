<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RackLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rack_id' => $this->rack_id,
            'level_number' => $this->level_number,
            'height_cm' => $this->height_cm,
            'max_weight_kg' => $this->max_weight_kg,
            'is_active' => (bool) $this->is_active,
            'slots' => RackSlotResource::collection($this->whenLoaded('slots')),
        ];
    }
}
