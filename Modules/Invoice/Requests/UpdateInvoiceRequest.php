<?php

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_invoice','web');
    }

    public function rules(): array
    {
        return [
            'lead_id'       => 'sometimes|required|integer|exists:leads,id',
            'clinic_id'     => 'sometimes|required|integer|exists:clinics,id',
            'report_id'     => 'nullable|integer|exists:reports,id',
            'services_cost' => 'sometimes|required|numeric|min:0',
            'supplies_cost' => 'sometimes|required|numeric|min:0',
            'total_cost'    => 'sometimes|required|numeric|min:0',
            'amount_paid'   => 'sometimes|required|numeric|min:0',
            'status'        => 'sometimes|required|in:unpaid,partial,paid',
            'issued_at'     => 'sometimes|required|date',
            'due_date'      => 'nullable|date',
        ];
    }
}
