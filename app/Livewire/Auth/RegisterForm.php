<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use App\Auth;

class RegisterForm extends Component
{
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $loading = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6|confirmed',
    ];

    protected $messages = [
        'email.required' => 'Поле email обов\'язкове для заповнення.',
        'email.email' => 'Введіть коректний email.',
        'password.required' => 'Поле пароль обов\'язкове для заповнення.',
        'password.min' => 'Пароль має містити не менше 6 символів.',
        'password.confirmed' => 'Паролі не співпадають.',
    ];

    public function register()
    {
        $this->validate();
        $this->loading = true;
        [$success, $result] = Auth::register([
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

    public function redirectToLogin()
    {
        $this->dispatch('openLoginModal');
    }

    public function render()
    {
        return view('livewire.auth.register-form');
    }
}
