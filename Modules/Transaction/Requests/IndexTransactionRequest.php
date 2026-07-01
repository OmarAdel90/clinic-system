<?php

namespace Modules\Transaction\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_warehouse_supplier_transaction','web');
    }

    public function rules(): array
    {
        return [];
    }
}
