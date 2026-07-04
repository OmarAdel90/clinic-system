<?php

namespace Modules\TreatmentPlan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_treatment_plan','web');
    }

    public function rules(): array
    {
        return [
            'lead_id'                                     => 'sometimes|integer|exists:leads,id',
            'user_id'                                     => 'sometimes|integer|exists:users,id',
            'clinic_id'                                   => 'sometimes|integer|exists:clinics,id',
            'diagnosis'                                   => 'nullable|string',
            'notes'                                       => 'nullable|string',
            'visits'                                      => 'nullable|array',
            'visits.*.scheduled_date'                     => 'required_with:visits|date',
            'visits.*.supplies_reserved'                  => 'nullable|array',
            'visits.*.supplies_reserved.*.sku'            => 'required_with:visits.*.supplies_reserved|string|max:100',
            'visits.*.supplies_reserved.*.name'           => 'required_with:visits.*.supplies_reserved|string|max:255',
            'visits.*.supplies_reserved.*.quantity'       => 'required_with:visits.*.supplies_reserved|integer|min:1',
            'visits.*.supplies_reserved.*.unit_price'     => 'required_with:visits.*.supplies_reserved|numeric|min:0',
        ];
    }
}
