<?php

namespace Modules\Pharmaceutical\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePharmaceuticalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_pharmaceutical','web');
    }

    public function rules(): array
    {
        return [
            'SKU'         => 'required|string|max:100|unique:pharmaceuticals,SKU',
            'name'        => 'required|string|max:255',
            'arabic_name' => 'nullable|string|max:255',
            'sale_price'  => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'attribute'   => 'nullable|array',
        ];
    }
}
