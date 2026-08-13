<?php

namespace App\Filament\Resources\Donations\Schemas;

use App\Enums\Currency;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DonationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Інформація про донат')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Сума')
                            ->disabled(),

                        Select::make('currency')
                            ->label('Валюта')
                            ->options([
                                Currency::UAH->value => 'UAH',
                                Currency::USD->value => 'USD',
                                Currency::EUR->value => 'EUR',
                            ])
                            ->disabled(),

                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Очікує',
                                'paid' => 'Оплачено',
                                'failed' => 'Помилка',
                                'refunded' => 'Повернено',
                            ])
                            ->disabled(),

                        TextInput::make('payment_method')
                            ->label('Метод оплати')
                            ->disabled(),

                        TextInput::make('payment_id')
                            ->label('ID транзакції')
                            ->disabled(),
                    ]),

                Section::make('Донатер')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_anonymous')
                            ->label('Анонімний')
                            ->disabled(),

                        TextInput::make('user.name')
                            ->label('Користувач')
                            ->disabled(),

                        TextInput::make('donor_name')
                            ->label("Ім'я донатера")
                            ->disabled(),

                        TextInput::make('donor_email')
                            ->label('Email донатера')
                            ->disabled(),

                        Select::make('donor_type')
                            ->label('Тип')
                            ->options([
                                'personal' => 'Фіз. особа',
                                'legal' => 'Юр. особа',
                            ])
                            ->disabled(),
                    ]),

                Section::make('Проєкт та бонус')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('project.title')
                            ->label('Проєкт')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('bonus.title')
                            ->label('Обраний бонус')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }
}
