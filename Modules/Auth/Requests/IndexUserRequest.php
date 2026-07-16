<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_user', 'web') || $this->user()->can('view_user', 'web');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
