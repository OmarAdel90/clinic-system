<?php

namespace Modules\Warehouse\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_warehouse','web');
    }

    public function rules(): array
    {
        return [
            'clinic_id' => 'required|integer|exists:clinics,id',
            'name'     => 'required|string|max:255',
        ];
    }
}
