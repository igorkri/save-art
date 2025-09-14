<?php

namespace App\Livewire\Components;

use Livewire\Component;

class Toast extends Component
{
    public string $type = 'red';
    public string $title = 'Помилка';
    public array|string $messages = [];
    public bool $show = false;

    public function mount(
        string $type = 'red',
        string $title = 'Помилка',
        array|string $messages = [],
        bool $show = false
    ): void {
        $this->type = $type;
        $this->title = $title;
        $this->messages = $messages;
        $this->show = $show;
    }

    public function close(): void
    {
        $this->show = false;
        $this->dispatch('toast-closed');
    }

    public function open(): void
    {
        $this->show = true;
        $this->dispatch('toast-opened');
    }

    public function render()
    {
        return view('livewire.components.toast');
    }
}
