<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileLegalRequest extends FormRequest
{
    /**
     * Визначає, чи авторизований користувач робити цей запит
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Підготовка даних перед валідацією
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('currency') && $this->currency) {
            $this->merge([
                'currency' => strtoupper($this->currency),
            ]);
        }
    }

    /**
     * Правила валідації для оновлення юридичного профілю
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
            'currency' => ['nullable', 'string', 'in:UAH,USD,EUR'],
            // logo може бути шляхом до файлу або base64 строкою
            'logo' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'edrpou' => ['nullable', 'string', 'max:50'],
            'authorized_person' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
