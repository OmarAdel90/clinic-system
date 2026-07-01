<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_supplier_payment_history','web');
    }

    public function rules(): array
    {
        return [];
    }
}
