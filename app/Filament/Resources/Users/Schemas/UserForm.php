<?php

namespace App\Filament\Resources\Users\Schemas;

use App\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->tabs([

                        // 🔹 Основна інформація про користувача
                        Tab::make('Користувач')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Облікові дані')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('ПІБ')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),

                                        Select::make('role')
                                            ->label('Роль користувача')
                                            ->options([
                                                UserRole::Developer->value => UserRole::Developer->getLabel(),
                                                UserRole::Admin->value => UserRole::Admin->getLabel(),
                                                UserRole::Moderator->value => UserRole::Moderator->getLabel(),
                                                UserRole::Owner->value => UserRole::Owner->getLabel(),
                                                UserRole::Mecenat->value => UserRole::Mecenat->getLabel(),
                                                UserRole::User->value => UserRole::User->getLabel(),
                                            ])
                                            ->required()
                                            ->default(UserRole::User->value),

                                        DateTimePicker::make('email_verified_at')
                                            ->label('Email підтверджено')
                                            ->disabled()
                                            ->nullable(),
                                    ])
                                    ->collapsible(),

                                Section::make('Пароль')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('password')
                                            ->label('Пароль при створенні')
                                            ->password()
                                            ->required(fn (string $context): bool => $context === 'create')
                                            ->disabled(fn (string $context): bool => $context === 'edit')
                                            ->dehydrated(fn (string $context): bool => $context !== 'edit')
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                            ->helperText(fn (string $context): string => $context === 'edit' ? 'Пароль не можна змінити тут. Використовуйте поле "Новий пароль".' : 'Мінімум 8 символів.'),

                                        TextInput::make('password_new')
                                            ->label('Новий пароль')
                                            ->password()
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->dehydrated(false)
                                            ->visible(fn (string $context) => $context === 'edit')
                                            ->helperText('Залиште порожнім, якщо не хочете змінювати пароль.'),

                                    ]),
                            ]),

                        // 🔹 Персональний профіль
                        Tab::make('Особистий профіль')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make('Аватар')
                                    ->columns(2)
                                    ->schema([
                                        Section::make('')
                                            ->description('Рекомендоване співвідношення 1:1 Максимальний розмір файлу: 1 МБ.')
                                            ->schema([
                                                FileUpload::make('profilePersonal.avatar')
                                                    ->label('Аватар')
                                                    ->image()
                                                    ->imageCropAspectRatio('1:1')
                                                    ->imageEditor()
                                                    ->maxSize(1024)
                                                    ->disk('public')
                                                    ->directory('avatars')
                                                    ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                                                    ->nullable(),
                                            ])
                                            ->columns(2),

                                        Section::make('')
                                            ->schema([
                                                Select::make('profilePersonal.role')
                                                    ->label('Роль/Посада')
                                                    ->options([
                                                        UserRole::Owner->value => UserRole::Owner->getLabel(),
                                                        UserRole::Mecenat->value => UserRole::Mecenat->getLabel(),
                                                        UserRole::User->value => UserRole::User->getLabel(),
                                                    ]),
                                                TextInput::make('profilePersonal.postal_code')->label('Поштовий індекс'),
                                            ])
                                            ->columns(1),
                                    ]),

                                Section::make('Персональні дані')
                                    ->columns(1)
                                    ->schema([
                                        LanguageTabs::make([
                                            TextInput::make('profilePersonal.full_name')->label('ПІБ'),
                                            TextInput::make('profilePersonal.profession')->label('Професія'),
                                            TextInput::make('profilePersonal.tags')->label('Теги'),
                                            TextInput::make('profilePersonal.country')->label('Країна'),
                                            TextInput::make('profilePersonal.region')->label('Область / Регіон'),
                                            TextInput::make('profilePersonal.city')->label('Місто'),
                                            Textarea::make('profilePersonal.description')
                                                ->label('Опис')
                                                ->rows(5)
                                                ->columnSpanFull(),
                                        ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        // 🔹 Юридичний профіль
                        Tab::make('Юридичний профіль')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Компанія')
                                    ->columns(2)
                                    ->schema([
                                        // Toggle::make('profileLegal.is_legal')
                                        //     ->label('Юридична особа')
                                        //     ->live(),

                                        FileUpload::make('profileLegal.logo')
                                            ->image()
                                            ->label('Логотип компанії')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditor()
                                            ->maxSize(1024)
                                            ->hint('Рекомендоване співвідношення 1:1. Максимальний розмір файлу: 1 МБ.')
                                            ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                                            ->directory('logos')
                                            ->disk('public')
                                            ->nullable(),

                                        Select::make('profileLegal.currency')
                                            ->options([
                                                'UAH' => 'Гривня (UAH)',
                                                'USD' => 'Долар (USD)',
                                                'EUR' => 'Євро (EUR)',
                                            ])
                                            ->label('Основна валюта')
                                            ->default('UAH'),
                                    ]),

                                Section::make('Реквізити компанії')
                                    ->columns(2)
                                    ->schema([
                                        LanguageTabs::make([
                                            TextInput::make('profileLegal.name')->label('Назва компанії'),
                                            TextInput::make('profileLegal.authorized_person')->label('Уповноважена особа'),
                                            TextInput::make('profileLegal.address')->label('Адреса'),
                                        ]),
                                        TextInput::make('profileLegal.phone')->label('Телефон'),
                                        TextInput::make('profileLegal.email')->label('Email')->email(),
                                        TextInput::make('profileLegal.edrpou')->label('ЄДРПОУ'),
                                    ])
                                    ->collapsible(),
                            ]),

                        // 🔹 Соціальні мережі
                        Tab::make('Соціальні мережі')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make('Профілі та посилання')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('profileSocial.website')
                                            ->label('Вебсайт')
                                            ->url()
                                            ->placeholder('https://example.com'),
                                        TextInput::make('profileSocial.facebook')
                                            ->label('Facebook')
                                            ->url()
                                            ->placeholder('https://facebook.com/...'),
                                        TextInput::make('profileSocial.instagram')
                                            ->label('Instagram')
                                            ->url()
                                            ->placeholder('https://instagram.com/...'),
                                        TextInput::make('profileSocial.linkedin')
                                            ->label('LinkedIn')
                                            ->url()
                                            ->placeholder('https://linkedin.com/in/...'),
                                        TextInput::make('profileSocial.twitter')
                                            ->label('Twitter')
                                            ->url()
                                            ->placeholder('https://x.com/...'),
                                        TextInput::make('profileSocial.telegram')
                                            ->label('Telegram')
                                            ->url()
                                            ->placeholder('https://t.me/...'),
                                        TextInput::make('profileSocial.youtube')
                                            ->label('YouTube')
                                            ->url()
                                            ->placeholder('https://youtube.com/...'),
                                        TextInput::make('profileSocial.tiktok')
                                            ->label('TikTok')
                                            ->url()
                                            ->placeholder('https://tiktok.com/@...'),
                                        TextInput::make('profileSocial.github')
                                            ->label('GitHub')
                                            ->url()
                                            ->placeholder('https://github.com/...'),
                                        TextInput::make('profileSocial.pinterest')
                                            ->label('Pinterest')
                                            ->url()
                                            ->placeholder('https://pinterest.com/...'),
                                        TextInput::make('profileSocial.whatsapp')
                                            ->label('WhatsApp')
                                            ->url()
                                            ->placeholder('https://wa.me/...'),
                                        TextInput::make('profileSocial.deviantart')
                                            ->label('DeviantArt')
                                            ->url()
                                            ->placeholder('https://deviantart.com/...'),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tab::make('Документи')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Завантажені документи')
                                    ->columns(2)
                                    ->schema([
                                        FileUpload::make('profileDocuments')
                                            ->label('Документи користувача')
                                            ->multiple()
                                            ->directory('profile_documents')
                                            ->disk('public')
                                            ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                                            ->helperText('Завантажте документи, пов\'язані з профілем користувача.')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(),
                            ]),

                        // 🔹 Додаткова інформація
                        Tab::make('Додаткова інформація')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Блокування користувача')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('is_blocked')
                                            ->label('Заблокувати користувача'),
                                        DateTimePicker::make('blocked_until')
                                            ->label('Дата закінчення блоку')
                                            ->seconds(true)
                                            ->helperText('Якщо вказано дату, блокування автоматично зніметься після її закінчення.'),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
