<?php

namespace App\Filament\Resources\Users\Schemas;

use App\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')
                    ->schema([
                        TextInput::make('name')
                            ->label('Ім\'я')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email адреса')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn ($record) => $record),
                    ]),

                Section::make('Роль та права доступу')
                    ->schema([
                        Select::make('role')
                            ->label('Роль користувача')
                            ->options(UserRole::getOptions())
                            ->default(UserRole::User->value)
                            ->required()
                            ->helperText('Тільки Розробник, Адміністратор та Модератор мають доступ до адмін-панелі'),
                    ]),

                Section::make('Безпека')
                    ->schema([
                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->minLength(8),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email підтверджено'),
                    ]),
            ]);
    }
}
