<?php

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_any_invoice','web') || $this->user()->can('view_invoice','web');
    }

    public function rules(): array
    {
        return [];
    }
}
