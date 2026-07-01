<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_supplier','web');
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ];
    }
}
