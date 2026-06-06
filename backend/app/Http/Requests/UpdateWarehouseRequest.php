<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('warehouse.update');
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse');

        return [
            'code' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('warehouses', 'code')->ignore($warehouseId)],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity_m2' => ['nullable', 'numeric', 'min:0'],
            'warehouse_type' => ['nullable', 'string', Rule::in(['reguler', 'cold_storage', 'bonded', 'konsinyasi'])],
            'pic_name' => ['nullable', 'string', 'max:100'],
            'pic_phone' => ['nullable', 'string', 'max:20'],
            'pic_email' => ['nullable', 'email', 'max:100'],
            'operating_hours' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
