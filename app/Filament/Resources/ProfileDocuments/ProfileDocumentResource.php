<?php

namespace App\Filament\Resources\ProfileDocuments;

use App\Filament\Resources\ProfileDocuments\Pages\CreateProfileDocument;
use App\Filament\Resources\ProfileDocuments\Pages\EditProfileDocument;
use App\Filament\Resources\ProfileDocuments\Pages\ListProfileDocuments;
use App\Filament\Resources\ProfileDocuments\Schemas\ProfileDocumentForm;
use App\Filament\Resources\ProfileDocuments\Tables\ProfileDocumentsTable;
use App\Models\ProfileDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProfileDocumentResource extends Resource
{
    protected static ?string $model = ProfileDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Користувачі';

    protected static ?string $recordTitleAttribute = 'file_path';

    protected static ?string $navigationLabel = 'Документи профілю';

    protected static ?string $pluralModelLabel = 'Документи профілю';

    protected static ?string $modelLabel = 'Документ профілю';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ProfileDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProfileDocumentsTable::configure($table);
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
            'index' => ListProfileDocuments::route('/'),
            'create' => CreateProfileDocument::route('/create'),
            'edit' => EditProfileDocument::route('/{record}/edit'),
        ];
    }
}
