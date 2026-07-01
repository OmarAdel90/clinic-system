<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowPatientFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_patient_feedback','web') || $this->user()->can('view_patient_feedback','web');
    }

    public function rules(): array
    {
        return [];
    }
}
