<?php

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_invoice','web');
    }

    public function rules(): array
    {
        return [];
    }
}
