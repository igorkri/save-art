<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Validate;
use Livewire\Form;

class RegisterForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    #[Validate('required|same:password')]
    public string $password_confirmation = '';

    #[Validate('accepted')]
    public bool $terms = false;

    /**
     * Правила валидации с использованием метода rules()
     * Это альтернатива атрибутам #[Validate]
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required', 'same:password'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * Кастомные сообщения об ошибках
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Поле "Ім\'я" є обов\'язковим.',
            'email.required' => 'Поле "Email" є обов\'язковим.',
            'email.email' => 'Введіть коректну email адресу.',
            'email.unique' => 'Користувач з такою email адресою вже існує.',
            'password.required' => 'Поле "Пароль" є обов\'язковим.',
            'password_confirmation.required' => 'Підтвердіть пароль.',
            'password_confirmation.same' => 'Паролі не співпадають.',
            'terms.accepted' => 'Ви повинні прийняти умови використання.',
        ];
    }

    /**
     * Створення користувача
     */
    public function store(): User
    {
        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Автоматично логінимо користувача після реєстрації
        Auth::login($user);

        // Очищуємо форму після успішної реєстрації
        $this->reset();

        return $user;
    }

    /**
     * Отримати лише потрібні поля для створення користувача
     */
    public function getUserData(): array
    {
        return $this->only(['name', 'email']);
    }
}
