<?php

namespace App\Filament\Resources\TermsBlocks;

use App\Filament\Resources\TermsBlocks\Pages\CreateTermsBlock;
use App\Filament\Resources\TermsBlocks\Pages\EditTermsBlock;
use App\Filament\Resources\TermsBlocks\Pages\ListTermsBlocks;
use App\Filament\Resources\TermsBlocks\Schemas\TermsBlockForm;
use App\Filament\Resources\TermsBlocks\Tables\TermsBlocksTable;
use App\Models\TermsBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TermsBlockResource extends Resource
{
    protected static ?string $model = TermsBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Умови використання';

    protected static ?string $navigationLabel = 'Блоки контенту';

    protected static ?string $modelLabel = 'Блок контенту';

    protected static ?string $pluralModelLabel = 'Блоки контенту';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return TermsBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TermsBlocksTable::configure($table);
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
            'index' => ListTermsBlocks::route('/'),
            'create' => CreateTermsBlock::route('/create'),
            'edit' => EditTermsBlock::route('/{record}/edit'),
        ];
    }
}
