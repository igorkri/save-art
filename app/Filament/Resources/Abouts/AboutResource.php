<?php

namespace App\Filament\Resources\Abouts;

use App\Filament\Resources\Abouts\Pages\CreateAbout;
use App\Filament\Resources\Abouts\Pages\EditAbout;
use App\Filament\Resources\Abouts\Pages\ListAbouts;
use App\Filament\Resources\Abouts\Schemas\AboutForm;
use App\Filament\Resources\Abouts\Tables\AboutsTable;
use App\Models\About;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AboutResource extends Resource
{
    protected static ?string $model = About::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $recordTitleAttribute = null;

    protected static ?string $navigationLabel = 'Про нас';

    protected static ?string $pluralModelLabel = 'Про нас';

    protected static ?string $modelLabel = 'Про нас';

    protected static ?int $navigationSort = 20;

    public static function getRecordTitle($record): ?string
    {
        return $record->title ?: 'About';
    }

    public static function form(Schema $schema): Schema
    {
        return AboutForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AboutsTable::configure($table);
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
            'index' => ListAbouts::route('/'),
            'create' => CreateAbout::route('/create'),
            'edit' => EditAbout::route('/{record}/edit'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        $record = About::first();
        if ($record) {
            return static::getUrl('edit', ['record' => $record]);
        } else {
            return static::getUrl('create');
        }
    }
}
