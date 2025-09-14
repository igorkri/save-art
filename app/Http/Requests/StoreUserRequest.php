<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Разрешаем всем создавать пользователей (регистрация)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'same:password'],
            'terms' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Ім\'я" є обов\'язковим.',
            'name.string' => 'Ім\'я повинно бути текстом.',
            'name.max' => 'Ім\'я не може бути довшим за 255 символів.',

            'email.required' => 'Поле "Email" є обов\'язковим.',
            'email.email' => 'Введіть коректну email адресу.',
            'email.unique' => 'Користувач з такою email адресою вже існує.',

            'password.required' => 'Поле "Пароль" є обов\'язковим.',
            'password_confirmation.required' => 'Підтвердіть пароль.',
            'password_confirmation.same' => 'Паролі не співпадають.',

            'terms.required' => 'Поле прийняття умов є обов\'язковим.',
            'terms.accepted' => 'Ви повинні прийняти умови використання.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'ім\'я',
            'email' => 'електронна пошта',
            'password' => 'пароль',
            'password_confirmation' => 'підтвердження паролю',
            'terms' => 'умови використання',
        ];
    }

    /**
     * Prepare the data for validation.
     * Это выполняется ДО валидации
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower($this->email),
            'name' => ucfirst(trim($this->name)),
        ]);
    }

    /**
     * Handle a passed validation attempt.
     * Это выполняется ПОСЛЕ успешной валидации
     */
    protected function passedValidation(): void
    {
        // Можно добавить дополнительную логику после успешной валидации
        // например, логирование или очистка данных
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Дополнительная валидация после основных правил
            if ($this->somethingElseIsInvalid()) {
                $validator->errors()->add('email', 'Что-то не так с этим email.');
            }
        });
    }

    /**
     * Пример дополнительной проверки
     */
    private function somethingElseIsInvalid(): bool
    {
        // Например, проверка на блокированные домены
        $blockedDomains = ['tempmail.com', '10minutemail.com'];
        $emailDomain = substr(strrchr($this->email, '@'), 1);

        return in_array($emailDomain, $blockedDomains);
    }
}
