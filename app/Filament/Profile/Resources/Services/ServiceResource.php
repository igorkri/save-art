<?php

namespace App\Filament\Profile\Resources\Services;

use App\Filament\Profile\Resources\Services\Pages\CreateService;
use App\Filament\Profile\Resources\Services\Pages\EditService;
use App\Filament\Profile\Resources\Services\Pages\ListServices;
use App\Filament\Profile\Resources\Services\Schemas\ServiceForm;
use App\Filament\Profile\Resources\Services\Tables\ServicesTable;
use App\Models\Service;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static UnitEnum|string|null $navigationGroup = 'Послуги';

    public static function getModelLabel(): string
    {
        return 'Послуга';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Послуги';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('serviceable_type', User::class)
            ->where('serviceable_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
