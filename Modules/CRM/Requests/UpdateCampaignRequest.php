<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_campaign','web');
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'ad_account_id' => 'nullable|string|max:100',
            'ad_account_name' => 'nullable|string|max:255',
            'platform'    => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'budget'      => 'nullable|numeric|min:0',
            'currency'    => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'status'      => 'nullable|in:draft,active,paused',
            'objective'   => 'nullable|string|max:255',
            'meta_source' => 'nullable|string|max:50',
            'spend'       => 'nullable|numeric|min:0',
            'impressions' => 'nullable|integer|min:0',
            'clicks'      => 'nullable|integer|min:0',
            'ctr'         => 'nullable|numeric|min:0',
            'cpc'         => 'nullable|numeric|min:0',
            'results'     => 'nullable|numeric|min:0',
            'result_label' => 'nullable|string|max:255',
            'ad_sets'     => 'nullable|array',
            'metrics_synced_at' => 'nullable|date',
        ];
    }
}
