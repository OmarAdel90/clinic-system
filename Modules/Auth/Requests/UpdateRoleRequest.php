<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_role','web');
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? $this->route('role');

        return [
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $roleId,
        ];
    }
}
