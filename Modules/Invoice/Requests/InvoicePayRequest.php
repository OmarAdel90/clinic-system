<?php

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoicePayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_invoice','web');
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
        ];
    }
}
