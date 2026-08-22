<?php

namespace App\Filament\Profile\Resources\Notifications\Tables;

use App\Enums\ContactInfoRequestStatus;
use App\Models\ContactInfoRequest;
use App\Models\Notification;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class NotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid(['default' => 1])
            ->recordClasses('profile-notification-card-record')
            ->defaultSort('created_at', 'desc')
            ->columns([
                ViewColumn::make('notification')
                    ->label('')
                    ->view('filament.profile.notifications.notification-card')
                    ->extraAttributes(['class' => 'profile-notification-card-column']),
            ])
            ->recordActions([
                Action::make('grantContact')
                    ->label('Надати')
                    ->color('primary')
                    ->visible(fn (Notification $record): bool => $record->type->value === 'contact_request' && filled($record->data['contact_info_request_id'] ?? null))
                    ->action(function (Notification $record): void {
                        $request = ContactInfoRequest::query()
                            ->whereKey($record->data['contact_info_request_id'] ?? null)
                            ->where('owner_id', auth()->id())
                            ->first();

                        if (! $request || $request->status !== ContactInfoRequestStatus::Pending) {
                            return;
                        }

                        $request->update([
                            'status' => ContactInfoRequestStatus::Granted,
                            'decided_at' => now(),
                        ]);
                        app(NotificationService::class)->notifyContactGranted($request);
                        $record->markAsRead();
                    }),
                Action::make('rejectContact')
                    ->label('Відхилити')
                    ->color('gray')
                    ->visible(fn (Notification $record): bool => $record->type->value === 'contact_request' && filled($record->data['contact_info_request_id'] ?? null))
                    ->action(function (Notification $record): void {
                        $request = ContactInfoRequest::query()
                            ->whereKey($record->data['contact_info_request_id'] ?? null)
                            ->where('owner_id', auth()->id())
                            ->first();

                        if (! $request || $request->status !== ContactInfoRequestStatus::Pending) {
                            return;
                        }

                        $request->update([
                            'status' => ContactInfoRequestStatus::Rejected,
                            'decided_at' => now(),
                        ]);
                        app(NotificationService::class)->notifyContactRejected($request);
                        $record->markAsRead();
                    }),
                Action::make('viewProject')
                    ->label(__('profile_notifications.actions.view_project'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (Notification $record) => filled($record->data['project_slug'] ?? null))
                    ->url(fn (Notification $record) => rtrim(config('app.frontend_url'), '/').'/projects/'.$record->data['project_slug'])
                    ->openUrlInNewTab(),
                Action::make('markAsRead')
                    ->label(__('profile_notifications.actions.mark_as_read'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('gray')
                    ->visible(fn (Notification $record) => ! $record->is_read)
                    ->action(fn (Notification $record) => $record->markAsRead()),
                DeleteAction::make(),
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
