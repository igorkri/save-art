<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use App\Auth;


class RegisterForm extends Component
{
    // Для совместимости с Livewire: переключение на форму регистрации
    public function switchToRegister()
    {
        // Ничего не делаем, так как уже на форме регистрации
    }
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $terms = false;
    public $loading = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email',
        'password' => 'required|min:6|confirmed',
        'terms' => 'accepted',
    ];

    protected $messages = [
        'name.required' => 'Поле ім’я обов’язкове для заповнення.',
        'name.string' => 'Ім’я повинно бути рядком.',
        'name.max' => 'Ім’я не може бути довше 255 символів.',
        'email.required' => 'Поле email обов\'язкове для заповнення.',
        'email.email' => 'Введіть коректний email.',
        'password.required' => 'Поле пароль обов\'язкове для заповнення.',
        'password.min' => 'Пароль має містити не менше 6 символів.',
        'password.confirmed' => 'Паролі не співпадають.',
        'terms.accepted' => 'Ви повинні прийняти умови використання платформи.',
    ];

    public function register()
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('showNotification', 'Помилка', collect($e->validator->errors()->all())->join("\n"), 'red', true);
            throw $e;
        }
        $this->loading = true;
        [$success, $result] = Auth::register([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ]);
        if (! $success) {
            $this->loading = false;
            $this->dispatch('showNotification', 'Помилка', $result, 'red', true);
            return null;
        }
        $this->loading = false;
        $this->dispatch('showNotification', 'Успіх', 'Реєстрація виконана', 'green', true);
        return redirect()->intended('/');
    }

    public function switchToLogin()
    {
        $this->dispatch('switchToLogin');
    }

    public function render()
    {
        return view('livewire.auth.register-form');
    }
}
