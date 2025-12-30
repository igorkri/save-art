<?php

namespace App\Filament\Resources\Messages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Користувач')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label('Тема')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('content')
                    ->label('Повідомлення')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->content),
                TextColumn::make('direction')
                    ->label('Напрямок')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'user_to_admin' => 'Від користувача',
                        'admin_to_user' => 'Від адміна',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'user_to_admin' => 'info',
                        'admin_to_user' => 'success',
                        default => 'gray',
                    }),
                IconColumn::make('read_at')
                    ->label('Прочитано')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->read_at !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('project.title')
                    ->label('Проєкт')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('direction')
                    ->label('Напрямок')
                    ->options([
                        'user_to_admin' => 'Від користувача',
                        'admin_to_user' => 'Від адміна',
                    ]),
                SelectFilter::make('read_status')
                    ->label('Статус')
                    ->options([
                        'unread' => 'Непрочитані',
                        'read' => 'Прочитані',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'unread' => $query->whereNull('read_at'),
                            'read' => $query->whereNotNull('read_at'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
