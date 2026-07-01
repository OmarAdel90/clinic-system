<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_campaign_cost','web');
    }

    public function rules(): array
    {
        return [
            'campaign_id'          => 'sometimes|required|integer|exists:campaigns,id',
            'cost'                 => 'sometimes|required|numeric|min:0',
            'currency'             => 'nullable|string|max:10',
            'customer_cost'        => 'nullable|numeric|min:0',
            'converted_lead_count' => 'nullable|integer|min:0',
            'notes'                => 'nullable|string',
        ];
    }
}
