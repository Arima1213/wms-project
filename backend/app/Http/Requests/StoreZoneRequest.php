<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('zone.create');
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse');

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('zones', 'code')->where('warehouse_id', $warehouseId)],
            'name' => ['nullable', 'string', 'max:100'],
            'zone_type' => ['nullable', 'string', Rule::in(['fast_moving', 'slow_moving', 'heavy', 'cold', 'hazmat'])],
            'color' => ['nullable', 'string', 'max:7'],
            'temperature_range' => ['nullable', 'array'],
            'humidity_range' => ['nullable', 'array'],
            'allowed_product_types' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
