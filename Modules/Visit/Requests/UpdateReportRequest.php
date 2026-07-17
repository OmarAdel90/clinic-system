<?php

namespace Modules\Visit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_report');
    }

    public function rules(): array
    {
        return [
            'diagnosis' => 'sometimes|nullable|string',
            'treatment_notes' => 'sometimes|nullable|string',
            'body' => 'sometimes|nullable|string',
        ];
    }
}
