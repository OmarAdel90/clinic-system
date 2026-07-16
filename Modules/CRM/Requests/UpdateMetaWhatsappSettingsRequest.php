<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMetaWhatsappSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_token' => 'nullable|string',
            'phone_number_id' => 'nullable|string|max:100',
            'waba_id' => 'nullable|string|max:100',
            'verify_token' => 'required|string|max:255',
            'api_version' => 'required|string|max:20',
        ];
    }
}
