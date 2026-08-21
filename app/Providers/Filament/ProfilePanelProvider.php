<?php

namespace App\Providers\Filament;

use App\Filament\Profile\Pages\Auth\EditProfile;
use App\Filament\Profile\Pages\Auth\Login;
use App\Filament\Profile\Pages\Auth\Register;
use App\Http\Middleware\EnsureFilamentProfileIsComplete;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
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
            ->darkMode(true, isForced: true)
            ->themeSwitcher(false)
            ->defaultThemeMode(ThemeMode::Dark)
            ->colors([
                // Color::hex('#fecc39') генерує відтінки через фіксовану OKLCH-таблицю,
                // яка для 500-700 суттєво темнішає й втрачає насиченість (виглядає
                // брудно-гірчичним замість чистого бренд-жовтого) — тому палітра
                // задана вручну (тон/насиченість #fecc39, світлота підібрана так,
                // щоб 500 лишався точним брендовим кольором сайту save-art).
                'primary' => [
                    50 => '#fffbf0',
                    100 => '#fff6db',
                    200 => '#ffeebd',
                    300 => '#fee494',
                    400 => '#fed55d',
                    500 => '#fecc39',
                    600 => '#f9ba01',
                    700 => '#c69401',
                    800 => '#936e01',
                    900 => '#6a5001',
                    950 => '#4c3900',
                ],
            ])
            ->font('Wix Madefor Display')
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
/* Мовний прапорець (resources/views/vendor/filament-language-switcher) завжди
   2rem — аватар підганяємо під той самий розмір, щоб коло-іконки в топбарі
   виглядали однаковими, а не мали аватар помітно більшим за сусідів. */
.fi-topbar .fi-user-menu-trigger .fi-user-avatar {
    width: 2rem;
    height: 2rem;
}
.fi-sidebar .fi-user-menu-trigger .fi-user-avatar {
    width: 2.75rem;
    height: 2.75rem;
}
/* Бейдж непрочитаних сповіщень наполовину звисає за межі кнопки-дзвіночка
   (Filament: .fi-icon-btn-badge-ctn, translate -50%) — без відступу він
   налазить на сусідню іконку мови що на десктопі, що на мобільному. */
.fi-notifications-bell {
    margin-inline-end: 0.5rem;
}
.fi-notifications-bell .fi-icon-btn {
    width: 2rem;
    height: 2rem;
    margin: 0;
}
.fi-notifications-bell .fi-icon-btn .fi-icon {
    width: 1.25rem;
    height: 1.25rem;
}
/* Filament за замовчуванням лишає gap-y-8/py-8 (2rem) навколо заголовка
   сторінки й між секціями контенту (.fi-page-header-main-ctn, .fi-page-content)
   — на десктопі це просто повітря, на мобільному забирає половину екрана. */
.fi-page-header-main-ctn {
    gap: 1.5rem;
    padding-block: 1.5rem;
}
.fi-page-content {
    gap: 1.5rem;
}
@media (max-width: 640px) {
    /* Мобільна щільність змінює тільки вертикальний ритм. Горизонтальні
       відступи лишаються такими, як були, а розміри полів і кнопок не чіпаємо. */
    .fi-page-header-main-ctn {
        row-gap: 0.25rem !important;
        padding-block: 0.25rem !important;
    }
    .fi-page-content {
        row-gap: 0.25rem !important;
    }
    .fi-main {
        padding-inline: 0.375rem;
    }
    .fi-page-main.fi-page-main-has-sub-navigation,
    .fi-page.fi-page-has-sub-navigation .fi-page-main,
    .fi-simple-page-content {
        row-gap: 0.5rem !important;
    }
    .fi-sc.fi-sc-has-gap {
        row-gap: 0.75rem !important;
    }
    .fi-sc.fi-sc-has-gap.fi-sc-dense {
        row-gap: 0.5rem !important;
    }
    .fi-sc-wizard.fi-contained .fi-sc-wizard-step.fi-active {
        margin-top: 0 !important;
        padding: 10px 4px;
    }
    /* /profile/profile має два вкладені рівні Tabs; кожен активний Tab у
       Filament додає p-6. Ущільнюємо обидва рівні лише на сторінці профілю. */
    .profile-edit-tabs.fi-sc-tabs.fi-contained .fi-sc-tabs-tab.fi-active {
        margin-top: 0 !important;
        padding: 10px 4px !important;
    }
    .fi-section:not(.fi-section-not-contained) > .fi-section-header {
        padding-block: 0.5rem !important;
    }
    .fi-section:not(.fi-section-not-contained):not(.fi-divided)
        > .fi-section-content-ctn > .fi-section-content,
    .fi-section.fi-compact:not(.fi-section-not-contained):not(.fi-divided)
        > .fi-section-content-ctn > .fi-section-content {
        padding-block: 0.75rem !important;
    }
    .fi-section:not(.fi-section-not-contained).fi-divided
        > .fi-section-content-ctn > .fi-section-content > * {
        padding-block: 0.75rem !important;
    }
    .fi-section:not(.fi-section-not-contained)
        > .fi-section-content-ctn > .fi-section-footer {
        padding-block: 0.5rem !important;
    }
    .fi-section.fi-section-not-contained:not(.fi-aside),
    .fi-section.fi-section-not-contained:not(.fi-aside)
        > .fi-section-content-ctn {
        row-gap: 0.5rem !important;
    }
    .fi-ta-ctn .fi-ta-header-toolbar,
    .fi-ta-ctn .fi-ta-header {
        padding: 0.375rem;
    }
    .fi-ta-ctn .fi-pagination {
        padding: 0.25rem 0.375rem;
    }
    /* Рядок "Сортувати за" над контентом таблиці (fi-ta-filters-above-content-ctn)
       та сама сітка карток проєктів (fi-ta-content-grid) — за замовчуванням
       py-4/p-4 (1rem), звужуємо до мінімуму на телефоні. */
    .fi-ta-ctn .fi-ta-filters-above-content-ctn {
        padding: 0.375rem;
    }
    .fi-ta-ctn .fi-ta-content.fi-ta-content-grid {
        padding-block: 0.25rem !important;
        padding-inline: 0.375rem;
        row-gap: 0.25rem !important;
        column-gap: 0.5rem;
    }
    /* Filament задає горизонтальні padding-inline-start/end безпосередньо на
       fi-ta-record-content — їх не перекриваємо. Вертикальний py-4 знаходиться
       на батьківському fi-ta-record-content-ctn, тому обнуляємо лише його. */
    .fi-ta-ctn .fi-ta-content.fi-ta-content-grid
        .fi-ta-record.profile-project-card-record .fi-ta-record-content-ctn {
        row-gap: 0.25rem !important;
        padding-block: 0 !important;
    }
    /* Усередині картки ProjectsTable вертикальний відступ приходить від
       Stack::extraAttributes([class => p-2]), а проміжки — від ->space(1),
       який Filament рендерить як fi-gap-sm. Залишаємо горизонтальні 0.5rem. */
    .fi-ta-content-grid .fi-ta-record-content {
        margin-block: 0 !important;
        padding: 5px 0;
    }
    .fi-ta-content-grid .fi-ta-record-content .fi-ta-stack.p-2 {
        padding-block: 0.25rem !important;
        padding-inline: 0.5rem !important;
    }
    .fi-ta-content-grid .fi-ta-record-content .fi-ta-stack.fi-gap-sm {
        row-gap: 0.125rem !important;
    }
    /* ProjectsTable вже має власну rounded/border-картку всередині запису.
       Прибираємо дубльовані фон, ring і shadow зовнішнього fi-ta-record. */
    .fi-ta-content-grid .fi-ta-record.profile-project-card-record {
        background: transparent !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
}
/* Іконки "скачати"/"відкрити" у FileUpload за замовчуванням білі (розраховані
   на показ поверх темного фото-превью, як в інших кнопок FilePond, що мають
   власну темну кругову підкладку). Просто перефарбувати іконку недостатньо —
   на світлому фоні (як у PDF без власного превью) вона все одно губиться.
   Тому додаємо таку саму темну кругову підкладку через ::before. */
.filepond--download-icon,
.filepond--open-icon {
    position: relative !important;
    background-color: #fff !important;
    width: 1.375rem !important;
    height: 1.375rem !important;
    z-index: 1;
}
.filepond--download-icon::before,
.filepond--open-icon::before {
    content: "";
    position: absolute;
    inset: -5px;
    background: rgba(0, 0, 0, 0.65);
    border-radius: 9999px;
    z-index: -1;
}
.filepond--download-icon:hover::before,
.filepond--open-icon:hover::before {
    background: rgba(0, 0, 0, 0.85);
}

/* Мобільний topbar: пошук ховаємо за іконкою (розкривається по тапу через
   checkbox-хак, без Alpine/JS) — на десктопі лишається звичне поле пошуку.
   Розміри аватара/дзвіночка вирівняні під прапорець вище, для всіх екранів. */
@media (max-width: 640px) {
    .fi-topbar-end {
        gap: 0.75rem;
    }
    /* Позиціонуємо випадне поле пошуку відносно всього топбару (на всю
       ширину екрана), а не відносно .fi-topbar-end — той займає лише
       стільки місця, скільки потрібно для іконок праворуч, і був би вузьким. */
    .fi-topbar {
        position: relative;
    }

    .fi-mobile-search-toggle-checkbox {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .fi-mobile-search-toggle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        color: rgb(107 114 128);
        cursor: pointer;
    }
    .dark .fi-mobile-search-toggle-btn {
        color: rgb(156 163 175);
    }
    .fi-mobile-search-toggle-btn svg {
        width: 1.25rem;
        height: 1.25rem;
    }
    #fi-mobile-search-toggle:checked ~ .fi-mobile-search-toggle-btn {
        color: rgb(17 24 39);
        background-color: rgba(0, 0, 0, 0.05);
    }
    .dark #fi-mobile-search-toggle:checked ~ .fi-mobile-search-toggle-btn {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .fi-global-search-ctn {
        display: none;
    }
    #fi-mobile-search-toggle:checked ~ .fi-global-search-ctn {
        display: block;
        position: absolute;
        top: calc(100% + 0.5rem);
        inset-inline: 0.5rem;
        z-index: 40;
        padding: 0.5rem;
        border-radius: 0.75rem;
        background-color: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
    .dark #fi-mobile-search-toggle:checked ~ .fi-global-search-ctn {
        background-color: rgb(17, 24, 39);
    }
}

@media (min-width: 641px) {
    .fi-mobile-search-toggle-checkbox,
    .fi-mobile-search-toggle-btn {
        display: none;
    }
}
</style>
'))
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => filament()->isGlobalSearchEnabled()
                    ? Blade::render(<<<'BLADE'
                        <input type="checkbox" id="fi-mobile-search-toggle" class="fi-mobile-search-toggle-checkbox" />
                        <label for="fi-mobile-search-toggle" class="fi-mobile-search-toggle-btn" aria-label="{{ __('filament-panels::global-search.field.label') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </label>
                        BLADE)
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@auth <livewire:profile.notifications-bell /> @endauth'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => $this->pdfThumbnailPreviewScript(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => $this->progressStepperScrollToCurrentScript(),
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
                EnsureFilamentProfileIsComplete::class,
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

    /**
     * FilePond (виджет FileUpload) не вміє показувати кастомне превью для
     * не-image файлів — для PDF завжди рендерить загальну іконку документа
     * (немає server-side хука підмінити це). Тому підставляємо мініатюру
     * ({file}-thumb.png, згенеровану GenerateStageDocumentPdfThumbnail)
     * як background-image прямо в картку файлу через DOM-патч після рендеру.
     * Крихко щодо розмітки FilePond/Filament — може зламатись при апдейті пакету.
     */
    private function pdfThumbnailPreviewScript(): string
    {
        return <<<'HTML'
            <script>
                (function () {
                    function patchPdfPreviews(root) {
                        root.querySelectorAll('fieldset.filepond--file-wrapper').forEach(function (fieldset) {
                            if (fieldset.dataset.pdfPreviewApplied) return;

                            var legend = fieldset.querySelector('legend');
                            var name = legend ? legend.textContent.trim() : '';
                            if (!/\.pdf$/i.test(name)) return;

                            var link = fieldset.querySelector('a.filepond--download-icon, a.filepond--open-icon');
                            if (!link || !link.href) return;

                            fieldset.dataset.pdfPreviewApplied = '1';

                            var thumbUrl = link.href.replace(/\.pdf$/i, '-thumb.png');
                            var img = new Image();
                            img.onload = function () {
                                var fileDiv = fieldset.querySelector('.filepond--file');
                                if (!fileDiv) return;
                                fileDiv.style.backgroundImage = 'url(' + thumbUrl + ')';
                                fileDiv.style.backgroundSize = 'cover';
                                fileDiv.style.backgroundPosition = 'center';
                                fileDiv.style.minHeight = '8rem';
                                fileDiv.style.borderRadius = '0.5rem';
                            };
                            img.src = thumbUrl;
                        });
                    }

                    document.addEventListener('livewire:init', function () {
                        patchPdfPreviews(document);

                        new MutationObserver(function () {
                            patchPdfPreviews(document);
                        }).observe(document.body, {childList: true, subtree: true});
                    });
                })();
            </script>
            HTML;
    }

    /**
     * ProgressStepper (aureuserp) на мобільних скролиться в один рядок
     * (див. .ps-container[data-ps-direction="horizontal"] у theme.css) —
     * без автоскролу поточний статус може опинитись поза екраном, і його
     * доводиться шукати вручну. Тому після кожного (пере)рендеру степера
     * прокручуємо його контейнер так, щоб крок зі статусом "current"
     * опинився в центрі видимої області.
     */
    private function progressStepperScrollToCurrentScript(): string
    {
        return <<<'HTML'
            <script>
                (function () {
                    function scrollToCurrentStep(root) {
                        root.querySelectorAll('.ps-container').forEach(function (container) {
                            if (container.dataset.psScrolledToCurrent) return;

                            var current = container.querySelector('.ps-step[data-ps-status="current"]');
                            if (!current) return;

                            container.dataset.psScrolledToCurrent = '1';
                            current.scrollIntoView({block: 'nearest', inline: 'center'});
                        });
                    }

                    document.addEventListener('livewire:init', function () {
                        scrollToCurrentStep(document);

                        new MutationObserver(function () {
                            scrollToCurrentStep(document);
                        }).observe(document.body, {childList: true, subtree: true});
                    });
                })();
            </script>
            HTML;
    }
}
