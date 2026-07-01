<?php

namespace Modules\Clinic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_clinic','web');
    }

    public function rules(): array
    {
        return [];
    }
}
