<?php

namespace App\Filament\Profile\Resources\Projects\Tables;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordClasses('profile-project-card-record')
            ->recordUrl(null)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'user',
                'team',
                'artCategory.parent',
                'donations' => fn ($query) => $query
                    ->where('status', 'paid')
                    ->where('is_public', true)
                    ->with('user')
                    ->latest()
                    ->limit(5),
            ]))
            ->columns([
                View::make('filament.profile.projects.project-card')
                    ->schema([
                        TextColumn::make('title')
                            ->searchable()
                            ->sortable()
                            ->hidden(),
                        TextColumn::make('short_description')
                            ->searchable()
                            ->hidden(),
                        TextColumn::make('budget_collected')
                            ->sortable()
                            ->hidden(),
                        TextColumn::make('budget_goal')
                            ->sortable()
                            ->hidden(),
                        TextColumn::make('created_at')
                            ->sortable()
                            ->hidden(),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('profile_projects.table.status'))
                    ->options(ProjectStatus::getOptions()),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon(null)
                    ->hiddenLabel()
                    ->extraAttributes(['class' => 'profile-project-card-edit-action'])
                    ->visible(fn (Project $record): bool => $record->status->isEditable()
                        || $record->status->isPartiallyEditable()
                        || in_array($record->status, [ProjectStatus::Completed, ProjectStatus::Sold], true)),
            ]);
    }
}
