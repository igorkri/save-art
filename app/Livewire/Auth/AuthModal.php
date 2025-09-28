<?php
namespace App\Livewire\Auth;

use Livewire\Component;

class AuthModal extends Component
{
    public $showLogin = true;
    public $showReset = false;

    protected $listeners = [
        'switchToRegister' => 'showRegister',
        'switchToLogin' => 'showLoginForm',
        'switchToReset' => 'showResetForm',
    ];

    public function showRegister() {
        $this->showLogin = false;
        $this->showReset = false;
    }
    public function showLoginForm() {
        $this->showLogin = true;
        $this->showReset = false;
    }
    public function showResetForm() {
        $this->showLogin = false;
        $this->showReset = true;
    }

    public function render()
    {
        return view('livewire.auth.auth-modal');
    }
}
