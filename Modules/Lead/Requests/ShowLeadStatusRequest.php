<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_lead_status', 'web');
    }

    public function rules(): array
    {
        return [];
    }
}
