<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowCampaignCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_campaign_cost','web');
    }

    public function rules(): array
    {
        return [];
    }
}
