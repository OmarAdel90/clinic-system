<?php

namespace Modules\Pharmaceutical\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPharmaceuticalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_pharmaceutical','web');
    }

    public function rules(): array
    {
        return [];
    }
}
