<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroySupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_supplier','web');
    }

    public function rules(): array
    {
        return [];
    }
}
