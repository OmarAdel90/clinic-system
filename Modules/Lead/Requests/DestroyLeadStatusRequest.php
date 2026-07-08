<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_lead_status', 'web');
    }

    public function rules(): array
    {
        return [];
    }
}
