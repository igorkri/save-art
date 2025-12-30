<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('ФІО')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state)
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label('Email підтверджено')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_blocked')
                    ->label('Заблокований')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable(),
                TextColumn::make('blocked_until')
                    ->label('Блок до')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_blocked')
                    ->label('Статус блокування')
                    ->trueLabel('Тільки заблоковані')
                    ->falseLabel('Тільки активні'),
                \Filament\Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options(\App\UserRole::getOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('block')
                        ->label('Заблокувати')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Заблокувати користувача')
                        ->modalDescription('Ви впевнені, що хочете заблокувати цього користувача?')
                        ->modalSubmitActionLabel('Заблокувати')
                        ->form([
                            DateTimePicker::make('blocked_until')
                                ->label('Заблокувати до (опціонально)')
                                ->helperText('Залиште порожнім для безстрокового блокування')
                                ->nullable(),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'is_blocked' => true,
                                'blocked_until' => $data['blocked_until'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Користувача заблоковано')
                                ->success()
                                ->send();
                        })
                        ->hidden(fn (User $record): bool => $record->is_blocked),
                    Action::make('unblock')
                        ->label('Розблокувати')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Розблокувати користувача')
                        ->modalDescription('Ви впевнені, що хочете розблокувати цього користувача?')
                        ->modalSubmitActionLabel('Розблокувати')
                        ->action(function (User $record): void {
                            $record->update([
                                'is_blocked' => false,
                                'blocked_until' => null,
                            ]);

                            Notification::make()
                                ->title('Користувача розблоковано')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (User $record): bool => $record->is_blocked),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('blockSelected')
                        ->label('Заблокувати обраних')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Заблокувати обраних користувачів')
                        ->modalDescription('Ви впевнені, що хочете заблокувати всіх обраних користувачів?')
                        ->modalSubmitActionLabel('Заблокувати')
                        ->form([
                            DateTimePicker::make('blocked_until')
                                ->label('Заблокувати до (опціонально)')
                                ->helperText('Залиште порожнім для безстрокового блокування')
                                ->nullable(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function (User $record) use ($data): void {
                                $record->update([
                                    'is_blocked' => true,
                                    'blocked_until' => $data['blocked_until'] ?? null,
                                ]);
                            });

                            Notification::make()
                                ->title('Користувачів заблоковано: '.$records->count())
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('unblockSelected')
                        ->label('Розблокувати обраних')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Розблокувати обраних користувачів')
                        ->modalDescription('Ви впевнені, що хочете розблокувати всіх обраних користувачів?')
                        ->modalSubmitActionLabel('Розблокувати')
                        ->action(function (Collection $records): void {
                            $records->each(function (User $record): void {
                                $record->update([
                                    'is_blocked' => false,
                                    'blocked_until' => null,
                                ]);
                            });

                            Notification::make()
                                ->title('Користувачів розблоковано: '.$records->count())
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
