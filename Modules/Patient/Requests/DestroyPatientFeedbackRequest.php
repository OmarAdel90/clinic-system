<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPatientFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_patient_feedback','web');
    }

    public function rules(): array
    {
        return [];
    }
}
