<?php

namespace App\Filament\Resources\Users\Tables;

use App\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ім\'я')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email адреса')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->getLabel())
                    ->color(fn (UserRole $state): string => match($state) {
                        UserRole::Developer => 'danger',
                        UserRole::Admin => 'warning',
                        UserRole::Owner => 'success',
                        UserRole::Moderator => 'info',
                        UserRole::Mecenat => 'gray',
                        UserRole::User => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email_verified_at')
                    ->label('Email підтверджено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Не підтверджено'),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Роль')
                    ->options(UserRole::getOptions())
                    ->placeholder('Всі ролі'),

                SelectFilter::make('email_verified_at')
                    ->label('Email підтверджено')
                    ->options([
                        'verified' => 'Підтверджено',
                        'unverified' => 'Не підтверджено',
                    ])
                    ->query(fn ($query, $data) => match($data['value'] ?? null) {
                        'verified' => $query->whereNotNull('email_verified_at'),
                        'unverified' => $query->whereNull('email_verified_at'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редагувати'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Видалити обрані'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
