<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use App\Auth;

class PasswordResetForm extends Component
{
    public $email = '';
    public $new_password = '';
    public $new_password_confirmation = '';
    public $loading = false;

    protected $rules = [
        'email' => 'required|email',
        'new_password' => 'required|min:6|confirmed',
    ];

    protected $messages = [
        'email.required' => 'Поле email обов\'язкове для заповнення.',
        'email.email' => 'Введіть коректний email.',
        'new_password.required' => 'Поле пароль обов\'язкове для заповнення.',
        'new_password.min' => 'Пароль має містити не менше 6 символів.',
        'new_password.confirmed' => 'Паролі не співпадають.',
    ];

    public function resetPassword()
    {
        $this->validate();
        $this->loading = true;
        [$success, $result] = Auth::resetPassword($this->email, $this->new_password);
        if (! $success) {
            $this->loading = false;
            $this->dispatch('showNotification', 'Помилка', $result, 'red', true);
            return null;
        }
        $this->loading = false;
        $this->dispatch('showNotification', 'Успіх', 'Пароль змінено', 'green', true);
        return redirect()->intended('/');
    }

    public function render()
    {
        return view('livewire.auth.password-reset-form');
    }
}
