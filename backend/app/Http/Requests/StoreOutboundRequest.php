<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'destination_type' => 'nullable|string|max:100',
            'destination_reference' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.qty' => 'required_with:items|numeric|min:0.01',
            'items.*.uom_id' => 'nullable|exists:uoms,id',
            'items.*.notes' => 'nullable|string',
        ];
    }
}
