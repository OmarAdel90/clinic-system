<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_supplier','web');
    }

    public function rules(): array
    {
        return [
            'name'         => 'sometimes|required|string|max:255',
            'phone_number' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^\+?\d{7,20}$/'],
        ];
    }
}
