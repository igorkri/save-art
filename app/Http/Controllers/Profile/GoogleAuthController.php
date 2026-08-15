<?php

namespace App\Http\Controllers\Profile;

use App\Enums\ProfileType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirectUrl(route('profile.auth.google.callback'))
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('profile.auth.google.callback'))
                ->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth error (profile panel)', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('filament.profile.auth.login');
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            Notification::make()
                ->title(__('profile_panel.google_auth.no_email'))
                ->danger()
                ->send();

            return redirect()->route('filament.profile.auth.login');
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->is_blocked) {
                Notification::make()
                    ->title(__('profile_panel.google_auth.blocked'))
                    ->danger()
                    ->send();

                return redirect()->route('filament.profile.auth.login');
            }
        } else {
            $userName = $googleUser->getName() ?: $email;

            $user = User::create([
                'full_name' => $userName,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::User,
                'profile_type' => ProfileType::Artist,
                'slug' => Str::slug($userName).'-'.Str::random(6),
            ]);
        }

        if (! $user->canAccessPanel(Filament::getPanel('profile'))) {
            Notification::make()
                ->title(__('profile_panel.google_auth.no_access'))
                ->danger()
                ->send();

            return redirect()->route('filament.profile.auth.login');
        }

        Auth::guard('web')->login($user, remember: true);

        request()->session()->regenerate();

        return redirect()->intended(Filament::getPanel('profile')->getUrl());
    }
}
