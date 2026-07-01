<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_supplier','web');
    }

    public function rules(): array
    {
        return [];
    }
}
