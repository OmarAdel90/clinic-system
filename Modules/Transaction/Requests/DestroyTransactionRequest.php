<?php

namespace Modules\Transaction\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_warehouse_supplier_transaction','web');
    }

    public function rules(): array
    {
        return [];
    }
}
