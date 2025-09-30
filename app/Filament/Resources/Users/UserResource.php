<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // icon: heroicon-o-users
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';


    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Користувачі';
    protected static ?string $pluralModelLabel = 'Користувачі';
    protected static ?string $modelLabel = 'Користувач';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['profileLegal', 'profilePersonal', 'profileSocial']);
    }

    public static function mapRecordDataToFormData(\Illuminate\Database\Eloquent\Model $record): array
    {
        $data = $record->toArray();

        // Преобразуем связанные данные в вложенные массивы для формы
        $data['profilePersonal'] = $record->profilePersonal ? $record->profilePersonal->toArray() : [];
        $data['profileLegal'] = $record->profileLegal ? $record->profileLegal->toArray() : [];
        $data['profileSocial'] = $record->profileSocial ? $record->profileSocial->toArray() : [];

        dd(['mapRecordDataToFormData' => $data]);

        return $data;
    }
}
