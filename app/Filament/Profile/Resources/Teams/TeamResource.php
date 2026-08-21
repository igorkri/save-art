<?php

namespace App\Filament\Profile\Resources\Teams;

use App\Filament\Profile\Resources\Teams\Pages\CreateTeam;
use App\Filament\Profile\Resources\Teams\Pages\EditTeam;
use App\Filament\Profile\Resources\Teams\Pages\ListTeams;
use App\Filament\Profile\Resources\Teams\Schemas\TeamForm;
use App\Filament\Profile\Resources\Teams\Tables\TeamsTable;
use App\Models\Team;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('profile_teams.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_teams.model.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('teamMembers', fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    public static function form(Schema $schema): Schema
    {
        return TeamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamsTable::configure($table);
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
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }
}
