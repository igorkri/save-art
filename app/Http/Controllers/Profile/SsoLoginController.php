<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\ProfileSsoToken;
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
    public function consume(string $token): RedirectResponse
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

        return redirect()->to($grant->redirect_path ?: '/profile/profile');
    }
}
