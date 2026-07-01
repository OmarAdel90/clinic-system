<?php

namespace Modules\Pharmaceutical\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexPharmaceuticalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_pharmaceutical','web');
    }

    public function rules(): array
    {
        return [];
    }
}
