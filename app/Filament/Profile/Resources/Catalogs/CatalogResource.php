<?php

namespace App\Filament\Profile\Resources\Catalogs;

use App\Filament\Profile\Resources\Catalogs\Pages\CreateCatalog;
use App\Filament\Profile\Resources\Catalogs\Pages\EditCatalog;
use App\Filament\Profile\Resources\Catalogs\Pages\ListCatalogs;
use App\Filament\Profile\Resources\Catalogs\Schemas\CatalogForm;
use App\Filament\Profile\Resources\Catalogs\Tables\CatalogsTable;
use App\Models\ArtCatalog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CatalogResource extends Resource
{
    protected static ?string $model = ArtCatalog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('profile_panel.nav_groups.works');
    }

    public static function getModelLabel(): string
    {
        return __('profile_catalogs.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_catalogs.model.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return CatalogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogs::route('/'),
            'create' => CreateCatalog::route('/create'),
            'edit' => EditCatalog::route('/{record}/edit'),
        ];
    }
}
