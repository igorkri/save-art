<?php

namespace App\Providers\Filament;

use App\Filament\Profile\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ProfilePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('profile')
            ->path('profile')
            ->login(Login::class)
            ->brandName('Кабінет автора')
            ->viteTheme('resources/css/filament/profile/theme.css')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Проєкти')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Роботи')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Послуги')
                    ->collapsible(),
            ])
            ->discoverResources(in: app_path('Filament/Profile/Resources'), for: 'App\Filament\Profile\Resources')
            ->discoverPages(in: app_path('Filament/Profile/Pages'), for: 'App\Filament\Profile\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Profile/Widgets'), for: 'App\Filament\Profile\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
