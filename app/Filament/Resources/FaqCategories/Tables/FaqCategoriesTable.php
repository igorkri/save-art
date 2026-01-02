<?php

namespace App\Filament\Resources\FaqCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FaqCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['uk'] ?? $state['en'] ?? '-') : $state)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                TextColumn::make('faqs_count')
                    ->label('Питань')
                    ->counts('faqs')
                    ->sortable(),

                TextColumn::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активна')
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
