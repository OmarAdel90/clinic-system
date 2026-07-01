<?php

namespace Modules\Transaction\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_warehouse_supplier_transaction','web');
    }

    public function rules(): array
    {
        return [];
    }
}
