<?php

namespace App\Providers\Filament;

use App\Filament\Profile\Pages\Auth\EditProfile;
use App\Filament\Profile\Pages\Auth\Login;
use App\Filament\Profile\Pages\Auth\Register;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ProfilePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        return $panel
            ->id('profile')
            ->path('profile')
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->brandName(fn (): string => trim(__('profile_panel.brand').' '.(auth()->user()?->full_name ?? '')))
            ->globalSearch()
            ->globalSearchDebounce('300ms')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->viteTheme('resources/css/filament/profile/theme.css')
            ->colors([
                'primary' => Color::Yellow,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn (): string => __('profile_panel.nav_groups.projects'))
                    ->collapsible(),
                NavigationGroup::make()
                    ->label(fn (): string => __('profile_panel.nav_groups.works'))
                    ->collapsible(),
                NavigationGroup::make()
                    ->label(fn (): string => __('profile_panel.nav_groups.services'))
                    ->collapsible(),
            ])
            ->discoverResources(in: app_path('Filament/Profile/Resources'), for: 'App\Filament\Profile\Resources')
            ->discoverPages(in: app_path('Filament/Profile/Pages'), for: 'App\Filament\Profile\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Profile/Widgets'), for: 'App\Filament\Profile\Widgets')
            ->widgets([])
            ->renderHook('panels::head.end', fn () => new HtmlString('
<style>
.fi-sidebar-nav-groups {
    gap: 0.625rem;
}
.fi-topbar .fi-user-menu-trigger .fi-user-avatar {
    width: 3rem;
    height: 3rem;
}
.fi-sidebar .fi-user-menu-trigger .fi-user-avatar {
    width: 2.75rem;
    height: 2.75rem;
}
.fi-sidebar-group {
    border-radius: 0.625rem;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
    background-color: rgba(0,0,0,0.015);
    gap: 0;
}
.dark .fi-sidebar-group {
    border-color: rgba(255,255,255,0.05);
    background-color: rgba(255,255,255,0.025);
}
.fi-sidebar-group-btn {
    background-color: rgba(0, 0, 0, 0.19);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    padding: 0.5rem 0.75rem;
    border-radius: 0;
}
.dark .fi-sidebar-group-btn {
    background-color: rgba(255,255,255,0.05);
    border-bottom-color: rgba(255,255,255,0.06);
}

.fi-sidebar-group-label {
    font-size: 0.6875rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: black;
}

.dark .fi-sidebar-group-label {
    color: white;
}

.fi-sidebar-group-items {
    padding: 0.375rem;
    gap: 0.125rem;
}
</style>
'))
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@auth <livewire:profile.notifications-bell /> @endauth'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => $this->googleAuthButton(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
                fn (): string => $this->googleAuthButton(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentLanguageSwitcherPlugin::make()
                    ->locales(['uk', 'en'])
                    ->rememberLocale(days: 30)
                    ->showOnAuthPages(),
            ])
            ->maxContentWidth(Width::Full)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Кнопка "Увійти через Google" для сторінок логіна та реєстрації панелі.
     * Прихована, якщо в .env не налаштовані GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET.
     */
    private function googleAuthButton(): string
    {
        if (blank(config('services.google.client_id'))) {
            return '';
        }

        return Blade::render(
            '<div class="fi-google-auth" style="margin-top:1rem;text-align:center;">
                <a href="{{ route(\'profile.auth.google.redirect\') }}" class="fi-btn fi-btn-color-gray fi-btn-outlined" style="display:inline-block;width:100%;padding:0.5rem;border:1px solid rgba(0,0,0,0.1);border-radius:0.5rem;text-decoration:none;text-align:center;">
                    {{ __(\'profile_panel.google_auth.button\') }}
                </a>
            </div>'
        );
    }
}
