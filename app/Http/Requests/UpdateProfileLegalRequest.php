<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileLegalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'currency' => ['nullable', 'in:UAH,USD,EUR'],
            'is_legal' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'string', 'max:255'],
            // Многоязычные JSON объекты
            'name' => ['nullable', 'array'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.uk' => ['nullable', 'string', 'max:255'],
            'edrpou' => ['nullable', 'string', 'max:50'],
            'authorized_person' => ['nullable', 'array'],
            'authorized_person.en' => ['nullable', 'string', 'max:255'],
            'authorized_person.uk' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'array'],
            'address.en' => ['nullable', 'string'],
            'address.uk' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
