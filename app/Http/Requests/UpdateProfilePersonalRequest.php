<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePersonalRequest extends FormRequest
{
    /**
     * Визначає, чи авторизований користувач робити цей запит
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Правила валідації для оновлення особистого профілю
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // avatar може бути шляхом до файлу (max:255) або base64 строкою
            'avatar' => ['nullable', 'string'],
            // Багатомовні JSON об'єкти
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
            'phone' => ['nullable', 'string', 'max:20'],
            'profile_type' => ['nullable', 'string', 'in:artist,patron'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.uk' => ['nullable', 'string'],
        ];
    }
}
