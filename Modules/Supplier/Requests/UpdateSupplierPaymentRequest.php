<?php

namespace Modules\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_supplier_payment_history','web');
    }

    public function rules(): array
    {
        return [
            'transaction_id' => 'sometimes|required|string|exists:warehouse_supplier_transactions,transaction_id',
            'supplier_id'    => 'sometimes|required|integer|exists:suppliers,id',
            'batch_id'       => 'sometimes|required|string|max:100',
            'total_amount'   => 'sometimes|required|numeric|min:0',
            'total_paid'     => 'sometimes|required|numeric|min:0',
            'payment_status' => 'sometimes|required|in:unpaid,partial,paid',
        ];
    }
}
