<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexLeadPickerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_lead', 'web') || $this->user()->can('view_lead', 'web');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:250',
        ];
    }
}
