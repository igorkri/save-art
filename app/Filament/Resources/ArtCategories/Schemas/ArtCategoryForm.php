<?php

namespace App\Filament\Resources\ArtCategories\Schemas;

use App\Models\ArtCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
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
                            ->options(fn (?Model $record) => self::buildCategoryTree($record))
                            ->nullable()
                            ->searchable()
                            ->placeholder('— Коренева категорія —'),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ArtCategory::class, 'slug', ignoreRecord: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('name')
                            ->label('Назва')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    /**
     * Будує ієрархічний список категорій з відступами.
     */
    private static function buildCategoryTree(?Model $record): array
    {
        $options = [];
        $currentId = $record?->id;

        // Отримуємо кореневі категорії
        $rootCategories = ArtCategory::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        foreach ($rootCategories as $root) {
            // Пропускаємо поточну категорію (не можна бути батьком самому собі)
            if ($currentId && $root->id === $currentId) {
                continue;
            }

            $options[$root->id] = $root->getLabel('uk');

            // Додаємо дочірні категорії з відступом
            $children = ArtCategory::where('parent_id', $root->id)
                ->orderBy('sort_order')
                ->get();

            foreach ($children as $child) {
                // Пропускаємо поточну категорію та її нащадків
                if ($currentId && $child->id === $currentId) {
                    continue;
                }

                $options[$child->id] = '— '.$child->getLabel('uk');
            }
        }

        return $options;
    }
}
