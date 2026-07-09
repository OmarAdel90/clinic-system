<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_supplier_payment_history','web');
    }

    public function rules(): array
    {
        return [
            'transaction_id' => 'required|integer|exists:warehouse_supplier_transactions,id',
            'supplier_id'    => 'required|integer|exists:suppliers,id',
            'total_amount'   => 'required|numeric|min:0',
            'total_paid'     => 'required|numeric|min:0',
            'payment_status' => 'required|in:unpaid,partial,paid',
        ];
    }
}
