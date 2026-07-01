<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_role','web');
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }
}
