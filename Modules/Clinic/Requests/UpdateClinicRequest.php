<?php

namespace Modules\Clinic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_clinic','web');
    }

    public function rules(): array
    {
        $clinicId = $this->route('clinic')?->id ?? $this->route('clinic');

        return [
            'name'                => 'sometimes|required|string|max:255|unique:clinics,name,' . $clinicId,
            'phone_number'        => 'sometimes|string|max:50',
            'address'             => 'sometimes|string|max:500',
            'provides_medication' => 'sometimes|boolean',
            'departments'         => 'sometimes|array',
            'departments.*'       => 'sometimes|string|max:255',
            'doctors'             => 'sometimes|array',
            'doctors.*'           => 'sometimes|string|max:255',
            'services'            => 'sometimes|array',
            'services.*'          => 'somtimes|max:255',
            'warehouse_id'        => 'sometimes|exists:warehouses,id'
        ];
    }
}
