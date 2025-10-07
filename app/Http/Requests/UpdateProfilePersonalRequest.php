<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePersonalRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь делать этот запрос
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Правила валидации для обновления личного профиля
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'string', 'max:255'],
            // Многоязычные JSON объекты
            'full_name' => ['nullable', 'array'],
            'full_name.en' => ['nullable', 'string', 'max:255'],
            'full_name.uk' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'array'],
            'profession.en' => ['nullable', 'string', 'max:255'],
            'profession.uk' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.en' => ['nullable', 'string', 'max:255'],
            'tags.uk' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'array'],
            'country.en' => ['nullable', 'string', 'max:255'],
            'country.uk' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'array'],
            'region.en' => ['nullable', 'string', 'max:255'],
            'region.uk' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'array'],
            'city.en' => ['nullable', 'string', 'max:255'],
            'city.uk' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.uk' => ['nullable', 'string'],
        ];
    }
}
