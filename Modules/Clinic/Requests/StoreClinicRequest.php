<?php

namespace Modules\Clinic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_clinic','web');
    }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:255|unique:clinics,name',
            'arabic_name'         => 'required|string|max:255|unique:clinics,arabic_name',
            'phone_number'        => 'required|string|max:50',
            'address'             => 'required|string|max:500',
            'provides_medication' => 'required|boolean',
            'departments'         => 'required|array',
            'departments.*'       => 'string|max:255',
            'services'            => 'required|array',
            'services.*'          => 'string|max:255',
            'doctors'             => 'nullable|array',
            'doctors.*'           => 'integer|max:255|exists:users,id',
        ];
    }
}
