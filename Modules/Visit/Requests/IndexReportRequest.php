<?php

namespace Modules\Visit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_report') || $this->user()->can('view_report');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'clinic_id' => 'nullable|integer|exists:clinics,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
