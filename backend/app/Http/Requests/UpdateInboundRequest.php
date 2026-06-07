<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_type' => 'nullable|string|max:100',
            'source_reference' => 'nullable|string|max:100',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:pending,received,cancelled',
        ];
    }
}
