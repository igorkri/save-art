<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('ФІО')
                    // дозволяємо копіювати значення стовпця
                    ->copyable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_blocked')
                    ->label('Заблокований')
                    ->sortable(),
                TextColumn::make('blocked_until')
                    ->label('Дата закінчення блоку')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_actually_blocked')
                    ->label('Заблокований користувач')
                    ->trueLabel('Тільки заблоковані')
                    ->falseLabel('Тільки активні'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
