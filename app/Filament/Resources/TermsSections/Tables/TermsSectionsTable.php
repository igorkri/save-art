<?php

namespace App\Filament\Resources\TermsSections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TermsSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('heading')
                    ->label('Заголовок')
                    ->formatStateUsing(fn (?string $state) => $state ?: '-')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('date')
                    ->label('Дата'),

                TextColumn::make('blocks_count')
                    ->label('Блоків')
                    ->counts('blocks')
                    ->sortable(),

                TextColumn::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->filters([
                //
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
