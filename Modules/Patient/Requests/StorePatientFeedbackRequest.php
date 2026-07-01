<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_patient_feedback','web');
    }

    public function rules(): array
    {
        return [
            'lead_id'       => 'required|integer|exists:leads,id',
            'clinic_id'     => 'required|integer|exists:clinics,id',
            'feedback_body' => 'required|string|max:5000',
        ];
    }
}
