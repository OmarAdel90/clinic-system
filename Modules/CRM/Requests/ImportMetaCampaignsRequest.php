<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportMetaCampaignsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_campaign', 'web');
    }

    public function rules(): array
    {
        return [
            'campaign_ids' => 'required|array|min:1',
            'campaign_ids.*' => 'required|string|max:100',
        ];
    }
}
