<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_campaign','web');
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'ad_account_id' => 'nullable|string|max:100',
            'platform'    => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'budget'      => 'nullable|numeric|min:0',
            'currency'    => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'status'      => 'nullable|in:draft,active,paused',
            'objective'   => 'nullable|string|max:255',
            'meta_source' => 'nullable|string|max:50',
        ];
    }
}
