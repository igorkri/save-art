<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginForm extends Component
{
    // Для совместимости с Livewire: переключение на форму регистрации
    public function switchToRegister()
    {
        $this->dispatch('switchToRegister');
    }
    public $email = '';
    public $password = '';
    public $loading = false;
    public $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    protected $messages = [
        'email.required' => 'Поле email обов\'язкове для заповнення.',
        'email.email' => 'Введіть коректний email.',
        'password.required' => 'Поле пароль обов\'язкове для заповнення.',
    ];

    public function login()
    {
        $this->validate();
        $this->loading = true;

        $throttleKey = strtolower($this->email) . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->loading = false;
            $this->dispatch('showNotification', 'Помилка', 'Забагато спроб. Спробуйте через ' . $seconds . ' сек.', 'red', true);
            return null;
        }

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->loading = false;
            $this->dispatch('showNotification', 'Помилка', 'Невірні дані для входу.', 'red', true);
            return null;
        }

        RateLimiter::clear($throttleKey);
        $this->loading = false;
        $this->dispatch('showNotification', 'Успіх', 'Вхід виконано', 'green', true);
        return redirect()->intended('/');
    }

    //redirectToRegister
    public function redirectToRegister()
    {
        $this->dispatch('openRegisterModal'); // Відкриваємо модальне вікно реєстрації
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
