<?php
namespace Modules\Patient\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_medical_record','web');
    }
    public function rules(): array
    {
        return [
            'type'  => 'sometimes|string|in:xray,lab,prescription,other',
            'file'  => 'sometimes|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}