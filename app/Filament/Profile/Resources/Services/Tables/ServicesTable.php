<?php

namespace App\Filament\Profile\Resources\Services\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->label('Зображення')
                    ->disk('public')
                    ->size(50),

                TextColumn::make('title')
                    ->label('Назва')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('artCategory.id')
                    ->label('Категорія')
                    ->formatStateUsing(fn ($record): string => $record->artCategory?->getLabel('uk') ?? '-'),

                TextColumn::make('price')
                    ->label('Ціна')
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                    ->sortable(),

                IconColumn::make('price_from')
                    ->label('«Від»')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
