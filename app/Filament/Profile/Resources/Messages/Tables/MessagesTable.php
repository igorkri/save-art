<?php

namespace App\Filament\Profile\Resources\Messages\Tables;

use App\Models\Message;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedEnvelopeOpen)
                    ->falseIcon(Heroicon::OutlinedEnvelope)
                    ->trueColor('gray')
                    ->falseColor('warning'),

                TextColumn::make('direction')
                    ->label(__('profile_messages.table.direction'))
                    ->badge()
                    ->formatStateUsing(fn (Message $record) => match (true) {
                        $record->isFromSystem() => __('profile_messages.direction.system'),
                        $record->isFromAdmin() => __('profile_messages.direction.admin'),
                        default => __('profile_messages.direction.you'),
                    })
                    ->color(fn (Message $record) => $record->isFromAdmin() ? 'info' : 'gray'),

                TextColumn::make('subject')
                    ->label(__('profile_messages.table.subject'))
                    ->placeholder('—')
                    ->weight(fn (Message $record) => $record->isFromAdmin() && ! $record->isRead() ? 'bold' : null),

                TextColumn::make('content')
                    ->label(__('profile_messages.table.content'))
                    ->limit(120)
                    ->wrap()
                    ->color('gray'),

                TextColumn::make('project.title')
                    ->label(__('profile_messages.table.project'))
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(__('profile_messages.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('markAsRead')
                    ->label(__('profile_messages.actions.mark_as_read'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('gray')
                    ->visible(fn (Message $record) => $record->isFromAdmin() && ! $record->isRead())
                    ->action(fn (Message $record) => $record->markAsRead()),
                Action::make('reply')
                    ->label(__('profile_messages.actions.reply'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('primary')
                    ->visible(fn (Message $record) => $record->isFromAdmin())
                    ->form([
                        Textarea::make('content')
                            ->label(__('profile_messages.form.content'))
                            ->required()
                            ->rows(5),
                    ])
                    ->action(function (Message $record, array $data): void {
                        $record->markAsRead();

                        Message::create([
                            'user_id' => $record->user_id,
                            'project_id' => $record->project_id,
                            'subject' => $record->subject ? "Re: {$record->subject}" : null,
                            'content' => $data['content'],
                            'direction' => Message::DIRECTION_USER_TO_ADMIN,
                        ]);

                        FilamentNotification::make()
                            ->title(__('profile_messages.actions.reply_success'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
