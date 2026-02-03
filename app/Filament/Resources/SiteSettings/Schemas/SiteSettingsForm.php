<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class SiteSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // Логотип
                Section::make('Логотип')
                    ->schema([
                        FileUpload::make('site_logo')
                            ->label('Логотип сайту')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
                            ->directory('site-settings/logos')
                            ->disk('public')
                            ->deleteUploadedFileUsing(function (string $file): void {
                                Storage::disk('public')->delete($file);
                            }),
                    ])
                    ->collapsible(),

                // Header секція
                Section::make('Шапка сайту (Header)')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('header_brand_name')
                                ->label('Назва бренду')
                                ->placeholder('save-art.in.ua')
                                ->maxLength(255),
                        ]),

                        Repeater::make('header_dropdown_sites')
                            ->label('Випадаючий список сайтів')
                            ->schema([
                                LanguageTabs::make([
                                    TextInput::make('name')
                                        ->label('Назва')
                                        ->placeholder('save-art.in.ua')
                                        ->required()
                                        ->maxLength(255),
                                ]),

                                TextInput::make('url')
                                    ->label('Посилання')
                                    ->placeholder('https://save-art.in.ua')
                                    ->url()
                                    ->required()
                                    ->maxLength(255),

                                Toggle::make('is_active')
                                    ->label('Активний')
                                    ->default(false),
                            ])
                            ->collapsible()
                            ->createItemButtonLabel('Додати сайт'),

                        Repeater::make('header_menu')
                            ->label('Меню навігації')
                            ->schema([
                                LanguageTabs::make([
                                    TextInput::make('label')
                                        ->label('Назва')
                                        ->placeholder('Проєкти')
                                        ->required()
                                        ->maxLength(255),
                                ]),

                                TextInput::make('url')
                                    ->label('Посилання')
                                    ->placeholder('/projects')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->collapsible()
                            ->createItemButtonLabel('Додати пункт меню'),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('header_socials.instagram')
                                    ->label('Instagram')
                                    ->url()
                                    ->placeholder('https://instagram.com/...')
                                    ->maxLength(255),

                                TextInput::make('header_socials.facebook')
                                    ->label('Facebook')
                                    ->url()
                                    ->placeholder('https://facebook.com/...')
                                    ->maxLength(255),

                                TextInput::make('header_socials.youtube')
                                    ->label('YouTube')
                                    ->url()
                                    ->placeholder('https://youtube.com/...')
                                    ->maxLength(255),
                            ]),

                        TextInput::make('header_support_button_url')
                            ->label('URL кнопки "Підтримати"')
                            ->placeholder('/support-platform')
                            ->maxLength(255),

                        LanguageTabs::make([
                            TextInput::make('header_support_button_text')
                                ->label('Текст кнопки "Підтримати"')
                                ->placeholder('Підтримати')
                                ->maxLength(50),
                        ]),

                        LanguageTabs::make([
                            TextInput::make('header_login_button_text')
                                ->label('Текст кнопки "Увійти"')
                                ->placeholder('Увійти')
                                ->maxLength(50),
                        ]),
                    ])
                    ->collapsible(),

                // Footer Top секція
                Section::make('Підвал — Верхня частина (Footer Top)')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('footer_brand_name')
                                ->label('Назва бренду')
                                ->placeholder('save-art.in.ua')
                                ->maxLength(255),
                        ]),

                        LanguageTabs::make([
                            TextInput::make('footer_slogan')
                                ->label('Слоган')
                                ->placeholder('Мистецтво допомоги — найсучасніше з мистецтв')
                                ->maxLength(255),
                        ]),

                        LanguageTabs::make([
                            TextInput::make('footer_collaboration_title')
                                ->label('Заголовок блоку співпраці')
                                ->placeholder('Запрошуємо експертів до співпраці')
                                ->maxLength(255),
                        ]),

                        LanguageTabs::make([
                            RichEditor::make('footer_collaboration_text')
                                ->label('Текст блоку співпраці')
                                ->placeholder('Благодійний фонд ID_Art UA відкритий до співпраці...')
                                ->maxLength(1000),
                        ]),

                        Repeater::make('footer_collaboration_items')
                            ->label('Елементи співпраці')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Зображення')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
                                    ->directory('site-settings/footer')
                                    ->disk('public'),

                                LanguageTabs::make([
                                    TextInput::make('text')
                                        ->label('Текст')
                                        ->placeholder('Створення сучасного українського мистецтва')
                                        ->required()
                                        ->maxLength(255),
                                ]),
                            ])
                            ->collapsible()
                            ->createItemButtonLabel('Додати елемент'),

                        LanguageTabs::make([
                            TextInput::make('footer_collaboration_button_text')
                                ->label('Текст кнопки')
                                ->placeholder('Відправити заявку')
                                ->maxLength(50),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Footer Middle секція - меню сайтів
                Section::make('Підвал — Меню сайтів (Footer Middle)')
                    ->schema([
                        Repeater::make('footer_sites_menu')
                            ->label('Меню сайтів')
                            ->schema([
                                LanguageTabs::make([
                                    TextInput::make('site_name')
                                        ->label('Назва сайту')
                                        ->placeholder('save-art.in.ua')
                                        ->required()
                                        ->maxLength(255),
                                ]),

                                TextInput::make('site_url')
                                    ->label('URL сайту')
                                    ->placeholder('/')
                                    ->required()
                                    ->maxLength(255),

                                Repeater::make('links')
                                    ->label('Посилання')
                                    ->schema([
                                        LanguageTabs::make([
                                            TextInput::make('label')
                                                ->label('Назва')
                                                ->placeholder('Проєкти')
                                                ->required()
                                                ->maxLength(255),
                                        ]),

                                        TextInput::make('url')
                                            ->label('Посилання')
                                            ->placeholder('/projects')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->collapsible()
                                    ->createItemButtonLabel('Додати посилання'),
                            ])
                            ->collapsible()
                            ->createItemButtonLabel('Додати сайт'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Footer Bottom секція - контактна інформація
                Section::make('Підвал — Контакти (Footer Bottom)')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('footer_company_name')
                                ->label('Назва компанії')
                                ->placeholder('БЛАГОДІЙНИЙ ФОНД ID_Art UA')
                                ->maxLength(255),
                        ]),

                        LanguageTabs::make([
                            TextInput::make('footer_address')
                                ->label('Адреса')
                                ->placeholder('м. Івано-Франківськ, Україна')
                                ->maxLength(255),
                        ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('footer_email')
                                    ->label('Email')
                                    ->email()
                                    ->placeholder('idartua.bo@gmail.com')
                                    ->maxLength(255),

                                TextInput::make('footer_phone')
                                    ->label('Телефон')
                                    ->tel()
                                    ->placeholder('+380 67 734 5938')
                                    ->maxLength(50),
                            ]),

                        Repeater::make('footer_social_links')
                            ->label('Соціальні мережі')
                            ->schema([
                                Select::make('type')
                                    ->label('Тип')
                                    ->options([
                                        'instagram' => 'Instagram',
                                        'facebook' => 'Facebook',
                                        'youtube' => 'YouTube',
                                        'telegram' => 'Telegram',
                                        'twitter' => 'Twitter/X',
                                        'tiktok' => 'TikTok',
                                    ])
                                    ->required(),

                                TextInput::make('url')
                                    ->label('Посилання')
                                    ->url()
                                    ->placeholder('https://...')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('label')
                                    ->label('Підпис')
                                    ->placeholder('@id_artUA')
                                    ->maxLength(100),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->createItemButtonLabel('Додати соцмережу'),

                        TextInput::make('footer_copyright_year')
                            ->label('Рік копірайту')
                            ->placeholder('2025')
                            ->maxLength(4),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
