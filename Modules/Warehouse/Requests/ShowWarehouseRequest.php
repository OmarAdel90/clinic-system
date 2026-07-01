<?php

namespace Modules\Warehouse\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_warehouse','web');
    }

    public function rules(): array
    {
        return [];
    }
}
