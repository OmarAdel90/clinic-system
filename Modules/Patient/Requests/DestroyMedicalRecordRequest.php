<?php

namespace Modules\Patient\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_medical_record','web');
    }

    public function rules(): array
    {
        return [];
    }
}
