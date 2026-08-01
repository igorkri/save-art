<?php

namespace App\Filament\Resources\TermsSections\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class TermsSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Інформація про розділ')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('heading')
                                ->label('Заголовок')
                                ->maxLength(500),
                        ])->columnSpanFull(),

                        TextInput::make('date')
                            ->label('Дата'),

                        TextInput::make('order')
                            ->label('Порядок сортування')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true),
                    ]),
            ]);
    }
}
