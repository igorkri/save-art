<?php

namespace App\Filament\Resources\ArtistBoards;

use App\Filament\Resources\ArtistBoards\Pages\CreateArtistBoard;
use App\Filament\Resources\ArtistBoards\Pages\EditArtistBoard;
use App\Filament\Resources\ArtistBoards\Pages\ListArtistBoards;
use App\Filament\Resources\ArtistBoards\Schemas\ArtistBoardForm;
use App\Filament\Resources\ArtistBoards\Tables\ArtistBoardsTable;
use App\Models\ArtistBoard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ArtistBoardResource extends Resource
{
    protected static ?string $model = ArtistBoard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    //    protected static ?string $recordTitleAttribute = 'titles.uk';
    protected static ?string $recordTitleAttribute = null;

    protected static ?string $navigationLabel = 'Дошка художників';

    protected static ?string $pluralModelLabel = 'Дошки художників';

    protected static ?string $modelLabel = 'Дошка художників';

    protected static ?int $navigationSort = 30;

    public static function getRecordTitle($record): ?string
    {
        return '10 художників в 10 національних музеях';
    }

    public static function form(Schema $schema): Schema
    {
        return ArtistBoardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArtistBoardsTable::configure($table);
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
            'index' => ListArtistBoards::route('/'),
            'create' => CreateArtistBoard::route('/create'),
            'edit' => EditArtistBoard::route('/{record}/edit'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        $record = ArtistBoard::first();
        if ($record) {
            return static::getUrl('edit', ['record' => $record]);
        } else {
            return static::getUrl('create');
        }
    }
}
