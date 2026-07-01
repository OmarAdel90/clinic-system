<?php

namespace Modules\Lead\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_lead','web');
    }

    public function rules(): array
    {
        return [];
    }
}
