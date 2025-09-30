<?php

namespace App\Filament\Resources\Users\Schemas;

use App\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make('Користувач (основне)')
                            ->schema([
                                TextInput::make('name')
                                    ->label('ПІБ') // из users
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Select::make('role')
                                    ->label('Роль') // из users
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
                                // password field only for create form
                                TextInput::make('password')
                                    ->label('Пароль')
                                    ->password()
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->disabled(fn (string $context): bool => $context === 'edit')
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null),
                                DateTimePicker::make('email_verified_at')
                                    ->label('Email підтверджено')
                                    ->disabled()
                                    ->nullable(),
                                TextInput::make('password_new')
                                    ->label('Новий пароль')
                                    ->password()
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->visible(fn (string $context) => $context === 'edit')
                                    ->helperText('Залиште порожній, якщо ви не хочете змінювати пароль.'),
                            ])
                            ->columns(2),
                        Tab::make('Профіль (особистий)')
                            ->columns(2)
                            ->schema([
                                FileUpload::make('profilePersonal.avatar')
                                    ->label('Аватар')
                                    ->image()
                                    ->imageCropAspectRatio('1:1')
                                    ->imageEditor()
                                    ->maxSize(1024) // Максимальный размер файла в килобайтах
                                    ->directory('avatars')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->nullable()
                                ,
                                TextInput::make('profilePersonal.full_name')
                                    ->label('ПІБ'),
                                TextInput::make('profilePersonal.profession')
                                    ->label('Професія'),
                                TextInput::make('profilePersonal.tags')
                                    ->label('Теги'),
                                TextInput::make('profilePersonal.country')
                                    ->label('Країна'),
                                TextInput::make('profilePersonal.region')
                                    ->label('Область/Регіон'),
                                TextInput::make('profilePersonal.city')
                                    ->label('Місто'),
                                TextInput::make('profilePersonal.postal_code')
                                    ->label('Поштовий індекс'),
                                TextInput::make('profilePersonal.role')
                                    ->label('Роль/Посада'),

                                Textarea::make('profilePersonal.description')
                                    ->label('Опис')->rows(6)->columnSpan('full'),
                            ]),
                        Tab::make('Профіль (юридичний)')
                            ->columns(2)
                            ->schema([
                                FileUpload::make('profileLegal.logo')
                                    ->image()
                                    ->maxSize(2048) // Максимальный размер файла в килобайтах
                                    // aspect ratio (1:1) - квадрат
                                    ->imageCropAspectRatio('1:1')
                                    ->imageEditor()
                                    ->directory('logos')
                                    ->disk('public')
                                    ->nullable()
                                    ->label('Логотип'),
                                Select::make('profileLegal.currency')
                                    ->options([
                                        'UAH' => 'Гривня (UAH)',
                                        'USD' => 'Долар (USD)',
                                        'EUR' => 'Євро (EUR)',
                                    ])
                                    ->label('Валюта'),
                                Toggle::make('profileLegal.is_legal')
                                    ->label('Юридична особа (1 - так, 0 - ні)'),
                                TextInput::make('profileLegal.name')
                                    ->label('Назва компанії'),
                                TextInput::make('profileLegal.edrpou')
                                    ->label('ЕДРПОУ'),
                                TextInput::make('profileLegal.authorized_person')
                                    ->label('Уповноважена особа'),
                                TextInput::make('profileLegal.address')
                                    ->label('Адреса'),
                                TextInput::make('profileLegal.phone')
                                    ->label('Телефон'),
                                TextInput::make('profileLegal.email')
                                    ->label('Email'),
                            ]),
                        Tab::make('Профіль (соціальні мережі)')
                            ->columns(2)
                            ->schema([
                                TextInput::make('profileSocial.website')
                                    ->url()
                                    ->label('Вебсайт'),
                                TextInput::make('profileSocial.facebook')->url(),
                                TextInput::make('profileSocial.twitter')->url(),
                                TextInput::make('profileSocial.instagram')->url(),
                                TextInput::make('profileSocial.linkedin')->url(),
                                TextInput::make('profileSocial.youtube')->url(),
                                TextInput::make('profileSocial.pinterest')->url(),
                                TextInput::make('profileSocial.github')->url(),
                                TextInput::make('profileSocial.telegram')->url(),
                                TextInput::make('profileSocial.tiktok')->url(),
                                TextInput::make('profileSocial.youtube_channel')->url(),
                                TextInput::make('profileSocial.whatsapp')->url(),
                                TextInput::make('profileSocial.deviantart')->url(),
                            ]),
                    ])
                ->columnSpan('full'),
            ]);
    }
}
