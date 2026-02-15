<?php

namespace App\Filament\Resources\ArtCategories\Schemas;

use App\Models\ArtCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArtCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Категорія мистецтва')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Батьківська категорія')
                            ->options(fn () => ArtCategory::whereNull('parent_id')->orderBy('sort_order')->get()->mapWithKeys(fn (ArtCategory $c) => [$c->id => $c->getLabel('uk')])->all())
                            ->nullable()
                            ->searchable(),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ArtCategory::class, 'slug', ignoreRecord: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('name.uk')
                            ->label('Назва (UK)')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name.en')
                            ->label('Назва (EN)')
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
