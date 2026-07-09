<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyCallCenterQueueEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_call_center_queue_entry', 'web');
    }

    public function rules(): array
    {
        return [];
    }
}
