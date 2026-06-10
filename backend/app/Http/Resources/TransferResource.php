<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_number' => $this->transfer_number,
            'source_warehouse_id' => $this->source_warehouse_id,
            'dest_warehouse_id' => $this->dest_warehouse_id,
            'status' => $this->status,
            'reason' => $this->reason,
            'expected_date' => $this->expected_date,
            'completed_date' => $this->completed_date,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'source_warehouse' => $this->whenLoaded('sourceWarehouse', fn() => [
                'id' => $this->sourceWarehouse->id,
                'code' => $this->sourceWarehouse->code,
                'name' => $this->sourceWarehouse->name,
            ]),
            'dest_warehouse' => $this->whenLoaded('destWarehouse', fn() => [
                'id' => $this->destWarehouse->id,
                'code' => $this->destWarehouse->code,
                'name' => $this->destWarehouse->name,
            ]),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'approved_by' => $this->whenLoaded('approvedByUser', fn() => [
                'id' => $this->approvedByUser->id,
                'name' => $this->approvedByUser->name,
            ]),
            'items' => TransferItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
