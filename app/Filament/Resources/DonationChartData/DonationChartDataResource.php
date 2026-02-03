<?php

namespace App\Filament\Resources\DonationChartData;

use App\Filament\Resources\DonationChartData\Pages\CreateDonationChartData;
use App\Filament\Resources\DonationChartData\Pages\EditDonationChartData;
use App\Filament\Resources\DonationChartData\Pages\ListDonationChartData;
use App\Filament\Resources\DonationChartData\Schemas\DonationChartDataForm;
use App\Filament\Resources\DonationChartData\Tables\DonationChartDataTable;
use App\Models\DonationChartData;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DonationChartDataResource extends Resource
{
    protected static ?string $model = DonationChartData::class;

    protected static ?string $slug = 'donation-chart-data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Проєкти';

    protected static ?string $navigationLabel = 'Графіки донатів';

    protected static ?string $pluralModelLabel = 'Графіки донатів';

    protected static ?string $modelLabel = 'Дані графіку';

    public static function form(Schema $schema): Schema
    {
        return DonationChartDataForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DonationChartDataTable::configure($table);
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
            'index' => ListDonationChartData::route('/'),
            'create' => CreateDonationChartData::route('/create'),
            'edit' => EditDonationChartData::route('/{record}/edit'),
        ];
    }
}
