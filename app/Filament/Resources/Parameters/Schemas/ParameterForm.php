<?php

namespace App\Filament\Resources\Parameters\Schemas;

use App\Enums\ParameterType;
use App\Models\ArtCategory;
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

                        Select::make('art_category_id')
                            ->label('Категорія мистецтва')
                            ->options(self::buildCategoryOptions())
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

    /**
     * Будує список категорій мистецтва з відступами для підкатегорій.
     *
     * @return array<int, string>
     */
    private static function buildCategoryOptions(): array
    {
        $options = [];

        $roots = ArtCategory::whereNull('parent_id')->orderBy('sort_order')->get();

        foreach ($roots as $root) {
            $options[$root->id] = $root->getLabel('uk');

            $children = ArtCategory::where('parent_id', $root->id)->orderBy('sort_order')->get();

            foreach ($children as $child) {
                $options[$child->id] = '— '.$child->getLabel('uk');
            }
        }

        return $options;
    }
}
