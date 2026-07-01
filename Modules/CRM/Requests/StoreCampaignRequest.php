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
            'platform'    => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'budget'      => 'nullable|numeric|min:0',
            'currency'    => 'nullable|string|max:10',
            'status'      => 'nullable|string|max:50',
        ];
    }
}
