<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => "Вкажіть ваше ім'я",
            'email.required' => 'Вкажіть email адресу',
            'email.email' => 'Невірний формат email',
            'email.unique' => 'Користувач з таким email вже існує',
            'password.required' => 'Вкажіть пароль',
            'password.confirmed' => 'Паролі не співпадають',
            'password.min' => 'Пароль має бути не менше 8 символів',
            'password.mixed' => 'Пароль має містити хоча б одну велику та одну малу літеру',
            'password.numbers' => 'Пароль має містити хоча б одну цифру',
        ];
    }
}
