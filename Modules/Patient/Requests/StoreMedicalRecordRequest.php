<?php
namespace Modules\Patient\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_medical_record','web');
    }
    public function rules(): array
    {
    //     if ($this->hasFile('file')) {
    //     dd(
    //         $this->file('file')->getMimeType(),
    //         $this->file('file')->getClientMimeType()
    //     );
    // }
        return [
            'type'  => 'required|string|in:xray,lab,prescription,other',
            'file'  => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}