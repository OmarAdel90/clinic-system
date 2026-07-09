<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallCenterQueueEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_call_center_queue_entry', 'web');
    }

    public function rules(): array
    {
        return [];
    }
}
