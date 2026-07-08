<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_lead_status', 'web');
    }

    public function rules(): array
    {
        $leadStatusId = $this->route('leadStatus')?->id;

        return [
            'label' => ['sometimes', 'string', 'max:255', Rule::unique('lead_status', 'label')->ignore($leadStatusId)],
            'key' => ['sometimes', 'string', 'max:255', Rule::unique('lead_status', 'key')->ignore($leadStatusId)],
            'color' => ['nullable', 'string', 'max:50'],
            'is_qualified' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ];
    }
}
