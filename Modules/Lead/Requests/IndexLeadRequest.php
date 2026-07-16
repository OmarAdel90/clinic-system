<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_lead','web') || $this->user()->can('view_lead','web');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'assignment_status' => 'nullable|in:assigned,unassigned',
            'clinic_assignment_status' => 'nullable|in:assigned,unassigned',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
