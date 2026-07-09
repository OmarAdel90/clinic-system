<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Lead\Models\Lead;

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
            'clinic_id'     => [
                'required',
                'integer',
                'exists:clinics,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $leadId = $this->integer('lead_id');
                    $lead = Lead::find($leadId);

                    if (! $lead?->clinic_id) {
                        $fail('The selected lead must already be assigned to a clinic before feedback can be recorded.');
                        return;
                    }

                    if ((int) $value !== (int) $lead->clinic_id) {
                        $fail('Patient feedback must use the clinic assigned to the selected lead.');
                    }
                },
            ],
            'feedback_body' => 'required|string|max:5000',
        ];
    }
}
