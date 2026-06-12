<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transfer.create');
    }

    public function rules(): array
    {
        return [
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'dest_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ];
    }
}
