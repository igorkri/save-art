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
            'full_name' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'profile_type' => ['nullable', 'string', 'in:artist,patron'],
            'description' => ['nullable', 'string'],
        ];
    }
}
