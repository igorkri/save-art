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
                            ])
                            ->columnSpan('full'),
                        Tab::make('Профіль (особистий)')
                            ->columns(2)
                            ->schema([
                                FileUpload::make('profilePersonal.avatar')
                                    ->label('Аватар')
                                    ->image()
                                    ->maxSize(1024) // Максимальный размер файла в килобайтах
                                    ->directory('avatars')
                                    ->visibility('public')
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
                                    ->directory('logos')
                                    ->visibility('public')
                                    ->nullable()
                                    ->label('Логотип'),
                                TextInput::make('profileLegal.currency')
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
                                TextInput::make('profileSocial.website')->url(),
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
