<?php

namespace Modules\Warehouse\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_warehouse','web');
    }

    public function rules(): array
    {
        return [
            'clinic_id' => 'sometimes|required|integer|exists:clinics,id',
            'items'     => 'nullable|array',
        ];
    }
}
