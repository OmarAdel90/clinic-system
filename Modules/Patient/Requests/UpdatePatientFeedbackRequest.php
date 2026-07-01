<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_patient_feedback','web');
    }

    public function rules(): array
    {
        return [
            'clinic_id'     => 'sometimes|required|integer|exists:clinics,id',
            'feedback_body' => 'sometimes|required|string|max:5000',
        ];
    }
}
