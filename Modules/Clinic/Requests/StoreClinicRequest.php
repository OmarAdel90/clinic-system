<?php

namespace Modules\Clinic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_clinic','web');
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'min:2', 'max:255', 'unique:clinics,name', 'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9&().,\'\/\-\s]+$/'],
            'arabic_name'         => ['required', 'string', 'max:255', 'unique:clinics,arabic_name', 'regex:/^(?=.*\p{Arabic})[\p{Arabic}\s\-\d]+$/u'],
            'phone_number'        => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-\(\)]{6,19}$/'],
            'address'             => 'required|string|max:500',
            'provides_medication' => 'required|boolean',
            'departments'         => 'required|array',
            'departments.*'       => 'string|max:255',
            'services'            => 'required|array|min:1',
            'services.*'          => [
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
            'doctors'             => 'nullable|array',
            'doctors.*'           => 'integer|max:255|exists:users,id',
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
