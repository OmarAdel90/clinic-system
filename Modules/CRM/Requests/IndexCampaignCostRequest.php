<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexCampaignCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_campaign_cost','web');
    }

    public function rules(): array
    {
        return [];
    }
}
