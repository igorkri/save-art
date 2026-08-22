<?php

namespace App\Filament\Resources\Parameters\Schemas;

use App\Enums\ParameterType;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class ParameterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Характеристика')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Translate::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Назва')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnSpanFull(),

                        SelectTree::make('art_category_id')
                            ->label('Категорія мистецтва')
                            ->relationship('artCategory', 'title', 'parent_id')
                            ->searchable()
                            ->required(),

                        Select::make('type')
                            ->label('Тип')
                            ->options(ParameterType::getOptions())
                            ->default(ParameterType::List->value)
                            ->live()
                            ->required(),

                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
