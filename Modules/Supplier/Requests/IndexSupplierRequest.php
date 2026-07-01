<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_supplier','web');
    }

    public function rules(): array
    {
        return [];
    }
}
