<?php

namespace App\Filament\Resources\ArtCategories\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class ArtCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Категорія мистецтва')
                    ->schema([
                        SelectTree::make('parent_id')
                            ->label('Батьківська категорія')
                            ->relationship(
                                relationship: 'parent',
                                titleAttribute: 'title',
                                parentAttribute: 'parent_id',
                                // Категорія не може бути батьком сама собі — виключаємо
                                // поточний запис з обох гілок дерева (кореневої та дочірньої).
                                modifyQueryUsing: fn (Builder $query, ?Model $record) => $record ? $query->whereKeyNot($record->getKey()) : $query,
                                modifyChildQueryUsing: fn (Builder $query, ?Model $record) => $record ? $query->whereKeyNot($record->getKey()) : $query,
                            )
                            ->searchable()
                            ->placeholder('— Коренева категорія —'),

                        Translate::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Назва')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
