<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginForm extends Component
{
    public $email = '';
    public $password = '';
    public $loading = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();
        $this->loading = true;
        $user = User::where('email', $this->email)->first();
        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->loading = false;
            $this->dispatch('showNotification', 'Помилка', 'Невірні дані', 'red', true);
            return;
        }
        // Генерируем токен (если нужно для API)
        $token = $user->createToken('web')->plainTextToken;
        session(['api_token' => $token]);
        Auth::login($user);
        $this->loading = false;
        $this->dispatch('showNotification', 'Успіх', 'Вхід виконано', 'green', true);
        // Можно добавить редирект или закрытие модального окна
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
