<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_lead_status', 'web');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255', Rule::unique('lead_status', 'label')],
            'key' => ['nullable', 'string', 'max:255', Rule::unique('lead_status', 'key')],
            'color' => ['nullable', 'string', 'max:50'],
            'is_qualified' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ];
    }
}
