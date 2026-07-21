<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\ArtCategory;
use App\Models\ImpersonationToken;
use App\Models\Project;
use App\Services\ModerationService;
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
                    ->label('Модерація')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->getLabel())
                    ->color(fn (ModerationStatus $state): string => match ($state) {
                        ModerationStatus::Pending => 'warning',
                        ModerationStatus::Processing => 'info',
                        ModerationStatus::Approved => 'success',
                        ModerationStatus::Rejected => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('artCategory.id')
                    ->label('Категорія')
                    ->formatStateUsing(fn ($record): string => $record->artCategory?->getLabel('uk') ?? '-')
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

                SelectFilter::make('art_category_id')
                    ->label('Категорія')
                    ->options(function () {
                        $opts = [];
                        foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
                            $opts[(string) $root->id] = $root->getLabel('uk');
                            foreach ($root->children as $child) {
                                $opts[(string) $child->id] = '  '.$child->getLabel('uk');
                            }
                        }

                        return $opts;
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('impersonate')
                        ->label('Увійти як автор')
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->color('gray')
                        ->visible(fn (): bool => auth()->user()->isAdmin())
                        ->requiresConfirmation()
                        ->modalHeading('Увійти на сайт під автором проєкту')
                        ->modalDescription('Відкриє фронтенд у новій вкладці, авторизованим під автором, одразу на цей проєкт у його кабінеті. Посилання одноразове й діє 2 хвилини.')
                        ->modalSubmitActionLabel('Увійти')
                        ->action(function (Project $record, $livewire) {
                            $grant = ImpersonationToken::issue($record->user, auth()->user(), $record->slug);
                            $url = rtrim(config('app.frontend_url'), '/').'/impersonate/'.$grant->token;

                            $livewire->js('window.open('.json_encode($url).', "_blank")');
                        }),

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
                            $moderationService = app(ModerationService::class);
                            $success = $moderationService->approveProject($record, auth()->user());

                            if ($success) {
                                Notification::make()
                                    ->title('Проєкт схвалено')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Помилка схвалення проєкту')
                                    ->danger()
                                    ->send();
                            }
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
                            $moderationService = app(ModerationService::class);
                            $success = $moderationService->rejectProject($record, auth()->user());

                            if ($success) {
                                Notification::make()
                                    ->title('Проєкт відхилено')
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Помилка відхилення проєкту')
                                    ->danger()
                                    ->send();
                            }
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
                            $moderationService = app(ModerationService::class);
                            $approvedCount = 0;

                            $records->each(function (Project $record) use ($moderationService, &$approvedCount): void {
                                if ($record->status === ProjectStatus::Moderation) {
                                    $success = $moderationService->approveProject($record, auth()->user());
                                    if ($success) {
                                        $approvedCount++;
                                    }
                                }
                            });

                            Notification::make()
                                ->title("Схвалено проектів: {$approvedCount}")
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
                            $moderationService = app(ModerationService::class);
                            $rejectedCount = 0;

                            $records->each(function (Project $record) use ($moderationService, &$rejectedCount): void {
                                if ($record->status === ProjectStatus::Moderation) {
                                    $success = $moderationService->rejectProject($record, auth()->user());
                                    if ($success) {
                                        $rejectedCount++;
                                    }
                                }
                            });

                            Notification::make()
                                ->title("Відхилено проектів: {$rejectedCount}")
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
