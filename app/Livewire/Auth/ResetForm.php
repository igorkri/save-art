<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Validate;

class ResetForm extends Component
{
    #[Validate('required|email')]
    public $email = '';

    #[Validate('required|min:8')]
    public $password = '';

    #[Validate('required|same:password')]
    public $password_confirmation = '';

    public $show = false;

    protected $listeners = ['openResetForm' => 'open', 'closeResetForm' => 'close'];

    public function open(): void
    {
        $this->reset(['email', 'password', 'password_confirmation']);
        $this->show = true;
        $this->dispatch('modal-fill-toggle', true);
    }

    public function close(): void
    {
        $this->show = false;
        $this->dispatch('modal-fill-toggle', false);
    }

    public function resetPassword(): void
    {
        $this->validate();

        // Здесь будет логика сброса пароля
        // Пока что просто показываем сообщение
        session()->flash('success', 'Інструкції для зміни паролю надіслано на вашу пошту.');
        $this->close();
    }

    public function render()
    {
        return view('livewire.auth.reset-form');
    }
}
