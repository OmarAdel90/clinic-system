<?php

namespace Modules\Visit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_report','web');
    }

    public function rules(): array
    {
        return [
            'diagnosis'                       => 'nullable|string',
            'treatment_notes'                 => 'nullable|string',
            'supplies_used'                   => 'nullable|array',
            'supplies_used.*.sku'             => 'required_with:supplies_used|string|max:100',
            'supplies_used.*.name'            => 'required_with:supplies_used|string|max:255',
            'supplies_used.*.quantity'        => 'required_with:supplies_used|integer|min:1',
            'supplies_used.*.unit_price'      => 'required_with:supplies_used|numeric|min:0',
            'body'                            => 'nullable|string',
        ];
    }
}
