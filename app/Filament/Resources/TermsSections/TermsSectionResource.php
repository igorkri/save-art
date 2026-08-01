<?php

namespace App\Filament\Resources\TermsSections;

use App\Filament\Resources\TermsSections\Pages\CreateTermsSection;
use App\Filament\Resources\TermsSections\Pages\EditTermsSection;
use App\Filament\Resources\TermsSections\Pages\ListTermsSections;
use App\Filament\Resources\TermsSections\Schemas\TermsSectionForm;
use App\Filament\Resources\TermsSections\Tables\TermsSectionsTable;
use App\Models\TermsSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TermsSectionResource extends Resource
{
    protected static ?string $model = TermsSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Умови використання';

    protected static ?string $navigationLabel = 'Розділи';

    protected static ?string $modelLabel = 'Розділ';

    protected static ?string $pluralModelLabel = 'Розділи';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return TermsSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TermsSectionsTable::configure($table);
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
            'index' => ListTermsSections::route('/'),
            'create' => CreateTermsSection::route('/create'),
            'edit' => EditTermsSection::route('/{record}/edit'),
        ];
    }
}
