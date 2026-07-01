<?php

namespace Modules\Pharmaceutical\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePharmaceuticalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_pharmaceutical','web');
    }

    public function rules(): array
    {
        return [
            'SKU'         => 'sometimes|required|string|max:100|unique:pharmaceuticals,SKU,' . $this->route('pharmaceutical') . ',SKU',
            'name'        => 'sometimes|required|string|max:255',
            'arabic_name' => 'nullable|string|max:255',
            'sale_price'  => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'attribute'   => 'nullable|array',
        ];
    }
}
