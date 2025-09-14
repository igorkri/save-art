<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginForm extends Component
{
    public $email = '';

    public $password = '';

    public $remember = false;

    public $show = false;

    protected $listeners = [
        'openLoginForm' => 'open',
        'closeLoginForm' => 'close',
        'openRegisterForm' => 'hide',
        'openResetForm' => 'hide',
    ];

    public function open()
    {
        // Reset form fields and открыть модал
        $this->show = true;
        $this->dispatch('modal-fill-toggle', true);
    }

    public function close()
    {
        $this->show = false;
        $this->dispatch('modal-fill-toggle', false);
    }

    public function hide(): void
    {
        $this->show = false;
        $this->dispatch('modal-fill-toggle', false);
    }

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Вкажіть email',
            'email.email' => 'Вкажіть коректний email',
            'password.required' => 'Вкажіть пароль',
        ]);

        $user = \App\Models\User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Невірний email або пароль.'],
            ]);
        }

        Auth::login($user, $this->remember);
        $this->reset(['email', 'password', 'remember']);
        $this->close();
        session()->flash('success', __('messages.welcome', ['name' => $user->name]));
        $this->redirect('/');
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
