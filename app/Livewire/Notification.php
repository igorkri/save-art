<?php

namespace App\Livewire;

use Livewire\Component;

class Notification extends Component
{
    public $title = '';
    public $message = '';
    public $show = false;
    public $class = 'red';
    public $autoClose = true;

    protected $listeners = ['showNotification' => 'showNotification'];

    public function mount($title = '', $message = '', $show = false, $class = 'red', $autoClose = true)
    {
        $this->title = $title ?? '';
        $this->message = $message ?? '';
        $this->show = $show ?? false;
        $this->class = $class ?? 'red';
        $this->autoClose = $autoClose ?? true;
    }

    public function showNotification($title, $message, $class = 'red', $autoClose = true)
    {
        $this->title = $title;
        $this->message = $message;
        $this->show = true;
        $this->class = $class;
        $this->autoClose = $autoClose;
    }

    public function closeNotification()
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.notification');
    }
}
