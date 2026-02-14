<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ArtCategory;
use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label('Обкладинка')
                    ->disk('public')
                    ->circular()
                    ->size(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title.uk')
                    ->label('Назва')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.display_name')
                    ->label('Автор')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->whereRaw("json_unquote(json_extract(full_name, '$.uk')) like ?", ["%{$search}%"])
                                ->orWhereRaw("json_unquote(json_extract(full_name, '$.en')) like ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->getLabel())
                    ->color(fn (ProjectStatus $state): string => match ($state) {
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
                    ->label('Модерація')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->getLabel())
                    ->color(fn (ModerationStatus $state): string => match ($state) {
                        ModerationStatus::Pending => 'warning',
                        ModerationStatus::Approved => 'success',
                        ModerationStatus::Rejected => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('art_category')
                    ->label('Категорія')
                    ->formatStateUsing(fn (?ArtCategory $state): string => $state?->getLabel() ?? '-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('budget_goal')
                    ->label('Ціль')
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('budget_collected')
                    ->label('Зібрано')
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('donors_count')
                    ->label('Меценатів')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('likes_count')
                    ->label('Лайків')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('announced_at')
                    ->label('Оголошено')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(ProjectStatus::getOptions()),

                SelectFilter::make('status_moderation')
                    ->label('Модерація')
                    ->options(ModerationStatus::getOptions()),

                SelectFilter::make('art_category')
                    ->label('Категорія')
                    ->options(ArtCategory::getOptions()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('approve')
                        ->label('Схвалити')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Схвалити проєкт')
                        ->modalDescription('Ви впевнені, що хочете схвалити цей проєкт? Він стане доступним для публіки.')
                        ->modalSubmitActionLabel('Так, схвалити')
                        ->visible(fn (Project $record): bool => $record->status === ProjectStatus::Moderation)
                        ->action(function (Project $record): void {
                            $record->update([
                                'status' => ProjectStatus::Announced,
                                'status_moderation' => ModerationStatus::Approved,
                                'announced_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Проєкт схвалено')
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->label('Відхилити')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Відхилити проєкт')
                        ->modalDescription('Ви впевнені, що хочете відхилити цей проєкт?')
                        ->modalSubmitActionLabel('Так, відхилити')
                        ->visible(fn (Project $record): bool => $record->status === ProjectStatus::Moderation)
                        ->action(function (Project $record): void {
                            $record->update([
                                'status' => ProjectStatus::Rejected,
                                'status_moderation' => ModerationStatus::Rejected,
                            ]);

                            Notification::make()
                                ->title('Проєкт відхилено')
                                ->warning()
                                ->send();
                        }),

                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_bulk')
                        ->label('Схвалити')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Схвалити обрані проєкти')
                        ->action(function (Collection $records): void {
                            $records->each(function (Project $record): void {
                                if ($record->status === ProjectStatus::Moderation) {
                                    $record->update([
                                        'status' => ProjectStatus::Announced,
                                        'status_moderation' => ModerationStatus::Approved,
                                        'announced_at' => now(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Проєкти схвалено')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('reject_bulk')
                        ->label('Відхилити')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Відхилити обрані проєкти')
                        ->action(function (Collection $records): void {
                            $records->each(function (Project $record): void {
                                if ($record->status === ProjectStatus::Moderation) {
                                    $record->update([
                                        'status' => ProjectStatus::Rejected,
                                        'status_moderation' => ModerationStatus::Rejected,
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Проєкти відхилено')
                                ->warning()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
