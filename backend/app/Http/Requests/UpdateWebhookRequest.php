<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:2048',
            'secret' => 'nullable|string|max:64',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'required|string|max:100',
            'is_active' => 'boolean',
            'headers' => 'nullable|array',
            'headers.*' => 'string|max:500',
        ];
    }
}
