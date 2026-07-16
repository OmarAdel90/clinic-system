<?php

namespace Modules\Clinic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        $can = $this->user()->can('view_any_clinic');
        \Illuminate\Support\Facades\Log::info('IndexClinicRequest authorize() called', [
            'user_id' => $this->user()?->id,
            'can_view_any_clinic' => $can,
        ]);
        return $can;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
