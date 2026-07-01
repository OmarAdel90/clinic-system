<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_user','web');
    }

    public function rules(): array
    {
        return [
            'roles'   => 'required|array',
            'roles.*' => 'integer|exists:roles,id',
        ];
    }
}
