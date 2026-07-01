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
            'visit_date'                          => 'sometimes|required|date',
            'next_visit_date'                     => 'nullable|date|after_or_equal:visit_date',
            'diagnosis'                           => 'nullable|string',
            'treatment_plan'                      => 'nullable|array',
            'treatment_duration'                  => 'nullable|string|max:255',
            'supplies_used'                       => 'nullable|array',
            'supplies_used.*.sku'                 => 'required_with:supplies_used|string|max:100',
            'supplies_used.*.name'                => 'required_with:supplies_used|string|max:255',
            'supplies_used.*.quantity'            => 'required_with:supplies_used|integer|min:1',
            'supplies_used.*.unit_price'          => 'required_with:supplies_used|numeric|min:0',
            'cost_known'                          => 'boolean',
            'body'                                => 'nullable|string',
            'status'                              => 'sometimes|in:active,completed,cancelled',
        ];
    }
}
