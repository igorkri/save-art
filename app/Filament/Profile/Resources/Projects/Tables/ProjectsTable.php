<?php

namespace App\Filament\Profile\Resources\Projects\Tables;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

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
            ->columns([
                Stack::make([
                    ImageColumn::make('cover')
                        ->label(__('profile_projects.table.cover'))
                        ->disk('public')
                        ->imageHeight('auto')
                        ->imageWidth('100%')
                        ->extraImgAttributes([
                            'class' => 'w-full h-auto rounded-t-xl',
                        ]),

                    Stack::make([
                        TextColumn::make('title')
                            ->label(__('profile_projects.table.title'))
                            ->limit(50)
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->size('lg'),

                        Split::make([
                            TextColumn::make('status')
                                ->label(__('profile_projects.table.status'))
                                ->badge()
                                ->formatStateUsing(fn (ProjectStatus $state): string => $state->getLabel())
                                ->color(fn (ProjectStatus $state): string => $state->getColor())
                                ->sortable()
                                ->grow(false),

                            // Модерація дублює статус поза межами самого етапу модерації
                            // (наприклад, для Announced вона завжди лишається Approved) —
                            // показуємо бейдж лише поки проєкт реально на модерації.
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
                                ->visible(fn (?Project $record): bool => $record?->status === ProjectStatus::Moderation)
                                ->grow(false),
                        ]),

                        Split::make([
                            TextColumn::make('budget_collected')
                                ->label(__('profile_projects.table.collected'))
                                ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                                ->sortable()
                                ->weight('semibold')
                                ->grow(false),

                            TextColumn::make('budget_goal')
                                ->label(__('profile_projects.table.goal'))
                                ->formatStateUsing(fn ($record, $state): string => '/ '.Number::currency(
                                    $state,
                                    $record->currency?->value ?? 'UAH',
                                    app()->getLocale(),
                                ))
                                ->color('gray')
                                ->sortable()
                                ->grow(false),
                        ]),

                        TextColumn::make('created_at')
                            ->label(__('profile_projects.table.created_at'))
                            ->dateTime('d.m.Y H:i')
                            ->sortable()
                            ->color('gray')
                            ->toggleable(isToggledHiddenByDefault: true),
                    ])->space(1)->extraAttributes(['class' => 'p-2']),
                ])->extraAttributes(['class' => 'rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden']),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('profile_projects.table.status'))
                    ->options(ProjectStatus::getOptions()),
            ])
            ->recordActions([
                //                ViewAction::make(),
                //                EditAction::make()
                //                    // Completed/Sold теж відкриваються — там лишається доступним
                //                    // лише крок "Фінальний результат" (ProjectPolicy::update()).
                //                    ->visible(fn ($record): bool => $record->status->isEditable()
                //                        || $record->status->isPartiallyEditable()
                //                        || in_array($record->status, [ProjectStatus::Completed, ProjectStatus::Sold], true)),
                //                DeleteAction::make()
                //                    ->visible(fn ($record): bool => $record->status->isEditable()),
            ]);
    }
}
