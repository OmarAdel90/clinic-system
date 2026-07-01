<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyCampaignCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_campaign_cost','web');
    }

    public function rules(): array
    {
        return [];
    }
}
