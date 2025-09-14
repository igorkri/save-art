<?php

namespace App\Livewire\Auth;

use App\Livewire\Forms\RegisterForm as RegisterFormObject;
use Livewire\Component;

class RegisterForm extends Component
{
    public RegisterFormObject $form;

    public bool $show = false;

    protected $listeners = [
        'openRegisterForm' => 'open',
        'closeRegisterForm' => 'close',
        'openLoginForm' => 'hide',
        'openResetForm' => 'hide',
    ];

    public function open(): void
    {
        $this->form->reset();
        $this->show = true;
        $this->dispatch('modal-fill-toggle', true);
    }

    public function close(): void
    {
        $this->show = false;
        $this->dispatch('modal-fill-toggle', false);
    }

    public function hide(): void
    {
        $this->show = false;
        $this->dispatch('modal-fill-toggle', false);
    }

    public function register(): void
    {
        try {
            $user = $this->form->store();

            $this->close();

            // Показываем уведомление об успешной регистрации
            session()->flash('success', 'Ласкаво просимо, '.$user->name.'!');

            // Перенаправляем на главную страницу
            $this->redirect('/');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Ошибки валидации автоматически отображаются
            throw $e;
        } catch (\Exception $e) {
            // Другие ошибки
            $this->addError('general', 'Виникла помилка при реєстрації. Спробуйте пізніше.');
        }
    }

    public function render()
    {
        return view('livewire.auth.register-form');
    }
}
