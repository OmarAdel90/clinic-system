<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_supplier_payment_history','web');
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
        ];
    }
}
