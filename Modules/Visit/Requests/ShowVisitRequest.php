<?php

namespace Modules\Visit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_visit','web') || $this->user()->can('view_visit','web');
    }

    public function rules(): array
    {
        return [];
    }
}
