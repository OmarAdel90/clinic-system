<?php

namespace Modules\Visit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_report') || $this->user()->can('view_any_report');
    }

    public function rules(): array
    {
        return [];
    }
}
