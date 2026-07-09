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
            'platform'    => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'budget'      => 'nullable|numeric|min:0',
            'currency'    => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'status'      => 'nullable|in:draft,active,paused',
        ];
    }
}
