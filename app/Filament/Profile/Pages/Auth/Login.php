<?php

namespace App\Filament\Profile\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    public function mount(): void
    {
        $this->forgetIntendedUrlFromOtherPanels();

        parent::mount();
    }

    public function authenticate(): ?LoginResponse
    {
        $this->forgetIntendedUrlFromOtherPanels();

        return parent::authenticate();
    }

    /**
     * Адмін і автори логіняться під одним guard'ом ('web'), тому session('url.intended'),
     * залишений після спроби зайти на /admin без авторизації, "протікає" в цю панель
     * і редіректить сюди залогіненого автора назад в адмінку. Скидаємо його, якщо він
     * веде не в цю панель.
     */
    private function forgetIntendedUrlFromOtherPanels(): void
    {
        $intended = session('url.intended');

        if (blank($intended)) {
            return;
        }

        if (! str($intended)->startsWith(url(Filament::getCurrentPanel()->getPath()))) {
            session()->forget('url.intended');
        }
    }
}
