<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_lead','web');
    }

    public function rules(): array
    {
        return [
            'platform'       => 'required|string|max:50',
            'whatsapp_id'    => 'nullable|string|max:100',
            'phone'          => 'required|string|max:20',
            'name'           => 'nullable|string|max:255',
            'profile_name'   => 'nullable|string|max:255',
            'metadata'       => 'nullable|array',
            'lead_status_id' => 'nullable|integer|exists:lead_status,id',
        ];
    }
}
