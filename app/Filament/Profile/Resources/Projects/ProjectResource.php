<?php

namespace App\Filament\Profile\Resources\Projects;

use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Filament\Profile\Resources\Projects\Pages\EditProject;
use App\Filament\Profile\Resources\Projects\Pages\ListProjects;
use App\Filament\Profile\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Profile\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('profile_panel.nav_groups.projects');
    }

    public static function getModelLabel(): string
    {
        return __('profile_projects.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_projects.model.plural');
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'short_description', 'code'];
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
