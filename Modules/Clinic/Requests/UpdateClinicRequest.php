<?php

namespace Modules\Clinic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_clinic','web');
    }

    public function rules(): array
    {
        $clinicId = $this->route('clinic')?->id ?? $this->route('clinic');

        return [
            'name'                => ['sometimes', 'required', 'string', 'min:2', 'max:255', 'unique:clinics,name,' . $clinicId, 'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9&().,\'\/\-\s]+$/'],
            'arabic_name'         => ['sometimes', 'required', 'string', 'max:255', 'unique:clinics,arabic_name,' . $clinicId, 'regex:/^(?=.*\p{Arabic})[\p{Arabic}\s\-\d]+$/u'],
            'phone_number'        => ['sometimes', 'required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-\(\)]{6,19}$/'],
            'address'             => 'sometimes|string|max:500',
            'provides_medication' => 'sometimes|boolean',
            'departments'         => 'sometimes|array',
            'departments.*'       => 'sometimes|string|max:255',
            'doctors'             => 'sometimes|array',
            'doctors.*'           => 'sometimes|integer|exists:users,id',
            'services'            => 'sometimes|array|min:1',
            'services.*'          => [
                'sometimes',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value)) {
                        if (trim($value) === '' || mb_strlen($value) > 255) {
                            $fail('Each service name must be a non-empty string up to 255 characters.');
                        }

                        return;
                    }

                    if (! is_array($value)) {
                        $fail('Each service must be either a name string or an object with name and cost.');
                        return;
                    }

                    $name = trim((string) ($value['name'] ?? ''));
                    $cost = $value['cost'] ?? null;

                    if ($name === '' || mb_strlen($name) > 255) {
                        $fail('Each service must include a valid name up to 255 characters.');
                    }

                    if (! is_numeric($cost) || floatval($cost) < 0) {
                        $fail('Each service must include a non-negative numeric cost.');
                    }
                },
            ],
            'warehouse_id'        => 'sometimes|nullable|exists:warehouses,id'
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Clinic name may only contain letters, numbers, spaces, and basic punctuation.',
            'phone_number.regex' => 'Phone number must contain only digits and standard phone symbols.',
        ];
    }
}
