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
                    ->label(__('profile_services.table.image'))
                    ->disk('public')
                    ->size(50),

                TextColumn::make('title')
                    ->label(__('profile_services.table.title'))
                    ->limit(50)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('artCategory.id')
                    ->label(__('profile_services.table.category'))
                    ->formatStateUsing(fn ($record): string => $record->artCategory?->getLabel('uk') ?? '-'),

                TextColumn::make('price')
                    ->label(__('profile_services.table.price'))
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                    ->sortable(),

                IconColumn::make('price_from')
                    ->label(__('profile_services.table.price_from'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('profile_services.table.created_at'))
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
