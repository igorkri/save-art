<?php

namespace App\Filament\Resources\FaqCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FaqCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Інформація про категорію')
                    ->schema([
                        TextInput::make('name')
                            ->label('Назва категорії')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('order')
                            ->label('Порядок сортування')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),
                    ]),
            ]);
    }
}
