<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string|max:50',
            'level' => 'nullable|integer|min:1',
            'position' => 'nullable|integer|min:0',
            'bin_type' => 'nullable|in:storage,picking,receiving,shipping,overflow,quarantine',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
