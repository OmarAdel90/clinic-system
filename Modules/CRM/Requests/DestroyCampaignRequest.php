<?php

namespace Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete_campaign','web');
    }

    public function rules(): array
    {
        return [];
    }
}
