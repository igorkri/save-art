<?php

namespace App\Filament\Resources\Parameters;

use App\Filament\Resources\Parameters\Pages\CreateParameter;
use App\Filament\Resources\Parameters\Pages\EditParameter;
use App\Filament\Resources\Parameters\Pages\ListParameters;
use App\Filament\Resources\Parameters\RelationManagers\ValuesRelationManager;
use App\Filament\Resources\Parameters\Schemas\ParameterForm;
use App\Filament\Resources\Parameters\Tables\ParametersTable;
use App\Models\Parameter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ParameterResource extends Resource
{
    protected static ?string $model = Parameter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Проєкти';

    protected static ?string $navigationLabel = 'Характеристики';

    protected static ?string $modelLabel = 'Характеристика';

    protected static ?string $pluralModelLabel = 'Характеристики';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return ParameterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParametersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParameters::route('/'),
            'create' => CreateParameter::route('/create'),
            'edit' => EditParameter::route('/{record}/edit'),
        ];
    }
}
