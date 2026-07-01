<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_campaign','web');
    }

    public function rules(): array
    {
        return [];
    }
}
