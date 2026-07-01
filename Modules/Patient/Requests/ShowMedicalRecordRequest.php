<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_medical_record','web') || $this->user()->can('view_medical_record','web');
    }

    public function rules(): array
    {
        return [];
    }
}
