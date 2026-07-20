<?php

namespace App\Filament\Resources\ArtCategories;

use App\Filament\Resources\ArtCategories\Pages\CreateArtCategory;
use App\Filament\Resources\ArtCategories\Pages\EditArtCategory;
use App\Filament\Resources\ArtCategories\Pages\ListArtCategories;
use App\Filament\Resources\ArtCategories\Schemas\ArtCategoryForm;
use App\Filament\Resources\ArtCategories\Tables\ArtCategoriesTable;
use App\Models\ArtCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Openplain\FilamentTreeView\Fields\TextField;
use Openplain\FilamentTreeView\Tree;
use UnitEnum;

class ArtCategoryResource extends Resource
{
    protected static ?string $model = ArtCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static UnitEnum|string|null $navigationGroup = 'Проєкти';

    protected static ?string $navigationLabel = 'Категорії мистецтва';

    protected static ?string $modelLabel = 'Категорія мистецтва';

    protected static ?string $pluralModelLabel = 'Категорії мистецтва';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ArtCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArtCategoriesTable::configure($table);
    }

    public static function tree(Tree $tree): Tree
    {
        return $tree
            ->fields([
                TextField::make('name')
                    ->formatStateUsing(fn (mixed $state, ArtCategory $record): string => $record->getLabel('uk')),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (ArtCategory $record): string => static::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArtCategories::route('/'),
            'create' => CreateArtCategory::route('/create'),
            'edit' => EditArtCategory::route('/{record}/edit'),
        ];
    }
}
