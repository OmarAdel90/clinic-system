<?php

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_invoice','web');
    }

    public function rules(): array
    {
        return [
            'lead_id'       => 'required|integer|exists:leads,id',
            'clinic_id'     => 'required|integer|exists:clinics,id',
            'report_id'     => 'nullable|integer|exists:reports,id',
            'services_cost' => 'required|numeric|min:0',
            'supplies_cost' => 'required|numeric|min:0',
            'total_cost'    => 'required|numeric|min:0',
            'amount_paid'   => 'required|numeric|min:0',
            'status'        => 'required|in:unpaid,partial,paid',
            'issued_at'     => 'required|date',
            'due_date'      => 'nullable|date',
        ];
    }
}
