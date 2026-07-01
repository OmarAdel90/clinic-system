<?php

namespace Modules\Warehouse\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_warehouse','web');
    }

    public function rules(): array
    {
        return [];
    }
}
