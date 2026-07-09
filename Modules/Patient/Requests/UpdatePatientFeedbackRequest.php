<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Patient\Models\PatientFeedback;

class UpdatePatientFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_patient_feedback','web');
    }

    public function rules(): array
    {
        /** @var PatientFeedback|null $feedback */
        $feedback = $this->route('patientFeedback');

        return [
            'clinic_id'     => [
                'sometimes',
                'required',
                'integer',
                'exists:clinics,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($feedback) {
                    $leadClinicId = $feedback?->lead?->clinic_id;

                    if (! $leadClinicId) {
                        $fail('The selected lead must already be assigned to a clinic before feedback can be updated.');
                        return;
                    }

                    if ((int) $value !== (int) $leadClinicId) {
                        $fail('Patient feedback must use the clinic assigned to the selected lead.');
                    }
                },
            ],
            'feedback_body' => 'sometimes|required|string|max:5000',
        ];
    }
}
