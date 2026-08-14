<?php

namespace App\Filament\Profile\Resources\UserPhotos;

use App\Filament\Profile\Resources\UserPhotos\Pages\CreateUserPhoto;
use App\Filament\Profile\Resources\UserPhotos\Pages\EditUserPhoto;
use App\Filament\Profile\Resources\UserPhotos\Pages\ListUserPhotos;
use App\Filament\Profile\Resources\UserPhotos\Schemas\UserPhotoForm;
use App\Filament\Profile\Resources\UserPhotos\Tables\UserPhotosTable;
use App\Models\UserPhoto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserPhotoResource extends Resource
{
    protected static ?string $model = UserPhoto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('profile_panel.nav_groups.works');
    }

    public static function getModelLabel(): string
    {
        return __('profile_user_photos.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_user_photos.model.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return UserPhotoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserPhotosTable::configure($table);
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
            'index' => ListUserPhotos::route('/'),
            'create' => CreateUserPhoto::route('/create'),
            'edit' => EditUserPhoto::route('/{record}/edit'),
        ];
    }
}
