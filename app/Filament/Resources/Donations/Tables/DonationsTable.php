<?php

namespace App\Filament\Resources\Donations\Tables;

use App\Enums\Currency;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DonationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('project.title.uk')
                    ->label('Проєкт')
                    ->limit(30)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Донатер')
                    ->default(fn ($record) => $record->is_anonymous ? 'Анонімний' : ($record->donor_name ?? '-'))
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Сума')
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Очікує',
                        'paid' => 'Оплачено',
                        'failed' => 'Помилка',
                        'refunded' => 'Повернено',
                        default => $state,
                    }),

                IconColumn::make('is_anonymous')
                    ->label('Анонім')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->toggleable(),

                TextColumn::make('donor_type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'personal' => 'Фіз. особа',
                        'legal' => 'Юр. особа',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bonus.title.uk')
                    ->label('Бонус')
                    ->limit(20)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paid_at')
                    ->label('Дата оплати')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Очікує',
                        'paid' => 'Оплачено',
                        'failed' => 'Помилка',
                        'refunded' => 'Повернено',
                    ]),

                SelectFilter::make('is_anonymous')
                    ->label('Анонімність')
                    ->options([
                        '1' => 'Анонімні',
                        '0' => 'Відкриті',
                    ]),

                SelectFilter::make('donor_type')
                    ->label('Тип донатера')
                    ->options([
                        'personal' => 'Фіз. особа',
                        'legal' => 'Юр. особа',
                    ]),

                SelectFilter::make('currency')
                    ->label('Валюта')
                    ->options([
                        Currency::UAH->value => 'UAH',
                        Currency::USD->value => 'USD',
                        Currency::EUR->value => 'EUR',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
