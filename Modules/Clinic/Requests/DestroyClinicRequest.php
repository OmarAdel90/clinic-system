<?php

namespace Modules\Clinic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_clinic','web');
    }

    public function rules(): array
    {
        return [];
    }
}
