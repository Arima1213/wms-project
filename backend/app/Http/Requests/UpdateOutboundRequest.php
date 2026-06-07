<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOutboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_type' => 'nullable|string|max:100',
            'destination_reference' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:pending,picking,shipped,cancelled',
        ];
    }
}
