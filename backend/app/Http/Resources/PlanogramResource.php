<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanogramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'version' => $this->version,
            'canvas_width' => $this->canvas_width,
            'canvas_height' => $this->canvas_height,
            'grid_size' => $this->grid_size,
            'canvas_data' => $this->canvas_data,
            'canvas_settings' => $this->canvas_settings,
            'updated_at' => $this->updated_at,
        ];
    }
}
