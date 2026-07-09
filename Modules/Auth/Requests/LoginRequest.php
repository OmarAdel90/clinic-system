<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'    => 'nullable|required_without:email|string|max:255',
            'email'    => 'nullable|required_without:login|string|max:255',
            'password' => 'required|string',
        ];
    }
}
