<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'length_cm' => $this->length_cm,
            'width_cm' => $this->width_cm,
            'height_cm' => $this->height_cm,
            'weight_kg' => $this->weight_kg,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'reorder_point' => $this->reorder_point,
            'safety_stock' => $this->safety_stock,
            'product_type' => $this->product_type,
            'track_batch' => (bool) $this->track_batch,
            'track_expiry' => (bool) $this->track_expiry,
            'hs_code' => $this->hs_code,
            'image_url' => $this->image_url,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Relationships if loaded
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'unit' => $this->whenLoaded('unit', fn() => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
        ];
    }
}
