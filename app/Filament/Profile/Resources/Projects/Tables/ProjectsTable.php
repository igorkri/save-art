<?php

namespace App\Filament\Profile\Resources\Projects\Tables;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label(__('profile_projects.table.cover'))
                    ->disk('public')
                    ->circular()
                    ->size(50),

                TextColumn::make('title')
                    ->label(__('profile_projects.table.title'))
                    ->limit(50)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('profile_projects.table.status'))
                    ->badge()
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->getLabel())
                    ->color(fn (ProjectStatus $state): string => match ($state) {
                        ProjectStatus::New => 'gray',
                        ProjectStatus::Draft => 'gray',
                        ProjectStatus::Moderation => 'warning',
                        ProjectStatus::Announced => 'info',
                        ProjectStatus::InProgress => 'primary',
                        ProjectStatus::Paused => 'gray',
                        ProjectStatus::Completed => 'success',
                        ProjectStatus::Sold => 'success',
                        ProjectStatus::Rejected => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('status_moderation')
                    ->label(__('profile_projects.table.moderation'))
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->getLabel())
                    ->color(fn (ModerationStatus $state): string => match ($state) {
                        ModerationStatus::Pending => 'warning',
                        ModerationStatus::Processing => 'info',
                        ModerationStatus::Approved => 'success',
                        ModerationStatus::Rejected => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('budget_goal')
                    ->label(__('profile_projects.table.goal'))
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                    ->sortable(),

                TextColumn::make('budget_collected')
                    ->label(__('profile_projects.table.collected'))
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('profile_projects.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('profile_projects.table.status'))
                    ->options(ProjectStatus::getOptions()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record): bool => $record->status->isEditable() || $record->status->isPartiallyEditable()),
                DeleteAction::make()
                    ->visible(fn ($record): bool => $record->status->isEditable()),
            ]);
    }
}
