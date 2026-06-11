<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'return_date' => 'nullable|date',
            'items' => 'nullable|array|min:1',
            'items.*.id' => 'nullable|exists:return_items,id',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.condition' => 'nullable|in:good,damaged,expired,defective',
            'items.*.resolution' => 'nullable|in:restock,discard,return_to_supplier',
            'items.*.refund_amount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
        ];
    }
}
