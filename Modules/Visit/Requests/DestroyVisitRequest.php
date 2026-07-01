<?php

namespace Modules\Visit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_visit','web');
    }

    public function rules(): array
    {
        return [];
    }
}
