<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\ProjectWorkflowService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestPendingProjects extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Проєкти на модерації';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Project::query()
                ->where('status', ProjectStatus::Moderation)
                ->latest()
                ->limit(5)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Назва')
                    ->limit(40)
                    ->url(fn (Project $record): string => ProjectResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('user.name')
                    ->label('Автор'),

                TextColumn::make('artCategory.id')
                    ->label('Категорія')
                    ->formatStateUsing(fn (Project $record) => $record->artCategory?->getLabel('uk') ?? '-'),

                TextColumn::make('budget_goal')
                    ->label('Бюджет')
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH'),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                Action::make('startReview')
                    ->label('Взяти в роботу')
                    ->icon('heroicon-o-play-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Project $record): void {
                        $started = app(ProjectWorkflowService::class)->startReview($record);

                        Notification::make()
                            ->title($started ? 'Проєкт взято в роботу' : 'Не вдалося взяти проєкт у роботу')
                            ->color($started ? 'success' : 'danger')
                            ->send();
                    }),

                Action::make('view')
                    ->label('Переглянути')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Project $record): string => ProjectResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
