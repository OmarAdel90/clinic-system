<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMetaFacebookSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facebook_page_id' => 'nullable|string|max:100',
            'facebook_page_access_token' => 'nullable|string',
            'instagram_access_token' => 'nullable|string',
            'ads_access_token' => 'nullable|string',
            'selected_ad_account_id' => 'nullable|string|max:100',
            'app_id' => 'nullable|string|max:100',
            'app_secret' => 'nullable|string',
            'verify_token' => 'required|string|max:255',
            'api_version' => 'required|string|max:20',
        ];
    }
}
