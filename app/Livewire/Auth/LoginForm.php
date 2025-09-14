<?php

namespace App\Livewire\Auth;

use Livewire\Component;

class LoginForm extends Component
{
    public $email = '';

    public $password = '';

    public $remember = false;

    public $show = false;

    protected $listeners = ['openLoginForm' => 'open', 'closeLoginForm' => 'close'];

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

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
