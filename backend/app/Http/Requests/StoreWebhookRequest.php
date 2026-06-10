<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $webhookId = $this->route('webhook');

        return [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'secret' => $webhookId ? 'sometimes|string|max:64' : 'required|string|max:64',
            'events' => 'required|array|min:1',
            'events.*' => 'required|string|max:100',
            'is_active' => 'boolean',
            'headers' => 'nullable|array',
            'headers.*' => 'string|max:500',
        ];
    }
}
