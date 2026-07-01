<?php

namespace Modules\Pharmaceutical\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowPharmaceuticalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_pharmaceutical','web');
    }

    public function rules(): array
    {
        return [];
    }
}
