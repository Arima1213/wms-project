<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'opname_number' => $this->opname_number,
            'warehouse_id' => $this->warehouse_id,
            'zone_id' => $this->zone_id,
            'type' => $this->type,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'warehouse' => $this->whenLoaded('warehouse', fn() => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),
            'zone' => $this->whenLoaded('zone', fn() => [
                'id' => $this->zone->id,
                'code' => $this->zone->code,
                'name' => $this->zone->name,
            ]),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'approved_by' => $this->whenLoaded('approvedByUser', fn() => [
                'id' => $this->approvedByUser->id,
                'name' => $this->approvedByUser->name,
            ]),
            'items' => StockOpnameItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
