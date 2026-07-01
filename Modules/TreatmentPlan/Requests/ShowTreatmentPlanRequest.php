<?php

namespace Modules\TreatmentPlan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_treatment_plan','web') || $this->user()->can('view_treatment_plan','web');
    }

    public function rules(): array
    {
        return [];
    }
}
