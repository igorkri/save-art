<?php

namespace App\Filament\Profile\Resources\Notifications\Tables;

use App\Models\Notification;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordClasses('profile-notification-card-record')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Stack::make([
                    Split::make([
                        IconColumn::make('is_read')
                            ->label('')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedEnvelopeOpen)
                            ->falseIcon(Heroicon::OutlinedEnvelope)
                            ->trueColor('gray')
                            ->falseColor('warning')
                            ->grow(false),

                        TextColumn::make('type')
                            ->label(__('profile_notifications.table.type'))
                            ->badge()
                            ->icon(fn (Notification $record) => $record->type->getIcon())
                            ->color(fn (Notification $record) => $record->type->getColor())
                            ->formatStateUsing(fn (Notification $record) => $record->type->getLabel()),

                        TextColumn::make('created_at')
                            ->label(__('profile_notifications.table.created_at'))
                            ->dateTime('d.m.Y H:i')
                            ->sortable()
                            ->color('gray')
                            ->grow(false),
                    ]),

                    TextColumn::make('title')
                        ->label(__('profile_notifications.table.title'))
                        ->getStateUsing(fn (Notification $record) => $record->title[app()->getLocale()] ?? $record->title['uk'] ?? '')
                        ->weight(fn (Notification $record) => $record->is_read ? 'semibold' : 'bold')
                        ->size('lg')
                        ->wrap(),

                    TextColumn::make('message')
                        ->label(__('profile_notifications.table.message'))
                        ->getStateUsing(fn (Notification $record) => $record->message[app()->getLocale()] ?? $record->message['uk'] ?? '')
                        ->limit(180)
                        ->wrap()
                        ->color('gray'),
                ])
                    ->space(2)
                    ->extraAttributes(['class' => 'p-3']),
            ])
            ->recordActions([
                Action::make('viewProject')
                    ->label(__('profile_notifications.actions.view_project'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (Notification $record) => filled($record->data['project_slug'] ?? null))
                    ->url(fn (Notification $record) => rtrim(config('app.frontend_url'), '/').'/project/'.$record->data['project_slug'])
                    ->openUrlInNewTab(),
                Action::make('markAsRead')
                    ->label(__('profile_notifications.actions.mark_as_read'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('gray')
                    ->visible(fn (Notification $record) => ! $record->is_read)
                    ->action(fn (Notification $record) => $record->markAsRead()),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('markAllAsRead')
                    ->label(__('profile_notifications.actions.mark_all_as_read'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('gray')
                    ->action(function (): void {
                        $count = app(NotificationService::class)->markAllAsRead(auth()->user());

                        FilamentNotification::make()
                            ->title(__('profile_notifications.actions.mark_all_as_read_success', ['count' => $count]))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
