<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_role','web');
    }

    public function rules(): array
    {
        return [
            'permissions'   => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }
}
