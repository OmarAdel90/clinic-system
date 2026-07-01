<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_lead','web');
    }

    public function rules(): array
    {
        return [
            'lead_id' => 'required|integer|exists:leads,id',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }
}
