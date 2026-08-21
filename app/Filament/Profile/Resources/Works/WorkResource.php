<?php

namespace App\Filament\Profile\Resources\Works;

use App\Filament\Profile\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Profile\Resources\Projects\Tables\ProjectsTable;
use App\Filament\Profile\Resources\Works\Pages\CreateWork;
use App\Filament\Profile\Resources\Works\Pages\EditWork;
use App\Filament\Profile\Resources\Works\Pages\ListWorks;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?int $navigationSort = 2;

    protected static bool $isGloballySearchable = false;

    public static function getModelLabel(): string
    {
        return __('profile_panel.navigation.work');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_panel.navigation.works');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema, __('profile_panel.works.owner_step'));
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorks::route('/'),
            'create' => CreateWork::route('/create'),
            'edit' => EditWork::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
