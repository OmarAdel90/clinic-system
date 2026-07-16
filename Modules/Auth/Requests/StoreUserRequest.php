<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        
        return $this->user()->can('create_user','web');
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'arabic_name'           => 'nullable|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email',
            'password'              => 'required|string|min:8',
            'role_id'               => 'nullable|integer|exists:roles,id',
            'SSN'                   => 'nullable|string|max:255',
            'phone_number'          => 'required|string|max:255|unique:users,phone_number',
            'location'              => 'nullable|string|max:255',
            'salary'                => 'nullable|numeric',
            'commission'            => 'nullable|numeric',
            'title'                 => 'nullable|string|max:255',
            'specialization'        => 'nullable|string|max:255',
            'hired_at'              => 'nullable|date',
            'whatsapp_agent_number' => 'nullable|string|max:255',
            'is_active'             => 'nullable|boolean',
            'work_start'            => 'nullable|string',
            'work_end'              => 'nullable|string',
            'roles'                 => 'nullable|array',
            'roles.*'               => 'integer|exists:roles,id',
        ];
    }
}
