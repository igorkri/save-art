<?php
namespace App\Livewire\Auth;

use Livewire\Component;

class AuthModal extends Component
{
    public $showLogin = true;

    protected $listeners = [
        'switchToRegister' => 'showRegister',
        'switchToLogin' => 'showLoginForm',
    ];

    public function showRegister() { $this->showLogin = false; }
    public function showLoginForm() { $this->showLogin = true; }

    public function render()
    {
        return view('livewire.auth.auth-modal');
    }
}
