<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_campaign','web');
    }

    public function rules(): array
    {
        return [];
    }
}
