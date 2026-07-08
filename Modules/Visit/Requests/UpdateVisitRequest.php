<?php

namespace Modules\Visit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_visit','web');
    }

    public function rules(): array
    {
        return [
            'lead_id'                             => 'sometimes|required|integer|exists:leads,id',
            'user_id'                             => 'sometimes|required|integer|exists:users,id',
            'clinic_id'                           => 'sometimes|required|integer|exists:clinics,id',
            'treatment_plan_id'                   => 'nullable|integer|exists:treatment_plans,id',
            'conversation_id'                     => 'nullable|integer|exists:conversations,id',
            'visit_number'                        => 'nullable|string|max:255',
            'visit_date'                          => 'sometimes|required|date',
            'supplies_reserved'                   => 'nullable|array',
            'supplies_reserved.*.sku'             => 'required_with:supplies_reserved|string|max:100',
            'supplies_reserved.*.name'            => 'required_with:supplies_reserved|string|max:255',
            'supplies_reserved.*.quantity'        => 'required_with:supplies_reserved|integer|min:1',
            'supplies_reserved.*.unit_price'      => 'required_with:supplies_reserved|numeric|min:0',
            'status'                              => 'sometimes|in:scheduled,confirmed,completed,cancelled,missed',
        ];
    }
}
