<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\MessagesOverview;
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
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Enums\Width;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Комунікація')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Проєкти')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Користувачі')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Контент')
                    ->collapsible(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                MessagesOverview::class,
            ])
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
            ->plugins([
                //                FilamentLanguageSwitcherPlugin::make()
                //                    ->locales([
                //                        ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
                //                        ['code' => 'uk', 'name' => 'Українська', 'flag' => 'ua'],
                //                    ]),
            ])
            ->sidebarCollapsibleOnDesktop() // Добавляем сворачиваемое меню на десктопе
            // ->sidebarFullyCollapsibleOnDesktop() // Альтернатива: полное сворачивание
            // Доступные варианты ширины:
            // В Filament v4 вы можете использовать следующие варианты ширины (от самой узкой до самой широкой):
            // Width::ExtraSmall - очень узкая
            // Width::Small - узкая
            // Width::Medium - средняя
            // Width::Large - большая
            // Width::ExtraLarge - очень большая
            // Width::TwoExtraLarge - 2xl
            // Width::ThreeExtraLarge - 3xl
            // Width::FourExtraLarge - 4xl
            // Width::FiveExtraLarge - 5xl
            // Width::SixExtraLarge - 6xl
            // Width::SevenExtraLarge - 7xl (по умолчанию)
            // Width::Full - на всю ширину страницы (установлено)
            ->maxContentWidth(Width::Full) // Устанавливаем максимальную ширину на всю страницу
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
