<?php

namespace Modules\TreatmentPlan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_treatment_plan','web');
    }

    public function rules(): array
    {
        return [];
    }
}
