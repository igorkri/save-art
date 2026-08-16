<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\ProfileSsoToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Приймає одноразовий SSO-грант, виданий SPA (save-art-web) по Sanctum
 * Bearer-токену через POST /v1/profile/sso-grant, і логінить користувача в
 * сесійний web-guard Filament-панелі "profile" — щоб перехід з кабінету SPA
 * не вимагав повторного вводу логіна/пароля.
 */
class SsoLoginController extends Controller
{
    public function consume(string $token): RedirectResponse|View
    {
        $grant = ProfileSsoToken::where('token', $token)->first();

        if (! $grant || ! $grant->isValid()) {
            return redirect()->route('filament.profile.auth.login');
        }

        // Одноразовий — одразу позначаємо використаним, незалежно від подальшого результату
        $grant->update(['used_at' => now()]);

        $user = $grant->user;

        if ($user->is_blocked) {
            return redirect()->route('filament.profile.auth.login');
        }

        Auth::guard('web')->login($user, remember: true);

        request()->session()->regenerate();

        // Навмисно НЕ звичайний redirect()->to() в тій самій відповіді, де щойно
        // встановили сесійну куку: браузер потрапив сюди cross-site навігацією
        // (window.location.href з save-art-web.ddev.site — інший (під)домен), і
        // деякі браузери відмовляються надсилати щойно встановлену Lax-куку на
        // наступний хоп ТОГО САМОГО ланцюжка редіректів (тому перший заход падав
        // на /profile/login, а другий заход на ту саму URL уже спрацьовував —
        // кука на той момент вже була в браузері). HTML-сторінка з client-side
        // редіректом розриває ланцюжок: фінальна навігація ініціюється вже з
        // save-art.ddev.site (same-site), і кука долітає гарантовано.
        return view('profile.sso-redirect', [
            'destination' => $grant->redirect_path ?: '/profile/profile',
        ]);
    }
}
