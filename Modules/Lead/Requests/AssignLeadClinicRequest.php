<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignLeadClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_lead', 'web');
    }

    public function rules(): array
    {
        return [
            'clinic_id' => 'required|integer|exists:clinics,id',
        ];
    }
}
