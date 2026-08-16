<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Поки користувач не зберіг обов'язкові поля профілю хоча б раз
 * (User::isProfileComplete(), проставляється в
 * App\Filament\Profile\Pages\Auth\EditProfile::handleRecordUpdate), у панелі
 * "profile" недоступно нічого, крім самої сторінки редагування профілю та
 * виходу з акаунту — інакше митець міг би, наприклад, одразу створювати
 * проєкти з порожнім профілем.
 */
class EnsureFilamentProfileIsComplete
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_ROUTE_NAMES = [
        'filament.profile.auth.profile',
        'filament.profile.auth.logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && ! $user->isProfileComplete()
            && ! in_array($request->route()?->getName(), self::ALLOWED_ROUTE_NAMES, true)
        ) {
            Notification::make()
                ->title(__('profile_edit.completion_required.title'))
                ->body(__('profile_edit.completion_required.body'))
                ->warning()
                ->send();

            return redirect()->route('filament.profile.auth.profile');
        }

        return $next($request);
    }
}
