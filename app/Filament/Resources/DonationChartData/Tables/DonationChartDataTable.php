<?php

namespace App\Filament\Resources\DonationChartData\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class DonationChartDataTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_type')
                    ->label('Період')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'day' => 'День',
                        'week' => 'Тиждень',
                        'month' => 'Місяць',
                        'year' => 'Рік',
                        'all' => 'Весь час',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'day' => 'info',
                        'week' => 'primary',
                        'month' => 'success',
                        'year' => 'warning',
                        'all' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Сума')
                    ->money('UAH')
                    ->sortable(),

                //                TextColumn::make('labels')
                //                    ->label('Точок')
                //                    ->formatStateUsing(fn ($state): string => is_array($state) ? count($state).' шт.' : '0 шт.')
                //                    ->badge()
                //                    ->color('gray'),

                IconColumn::make('is_manual')
                    ->label('Вручну')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil')
                    ->falseIcon('heroicon-o-cpu-chip')
                    ->trueColor('warning')
                    ->falseColor('success'),

                TextColumn::make('data_collected_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Змінено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            ])
            ->headerActions([
                \Filament\Actions\Action::make('collect')
                    ->label('Зібрати дані')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Зібрати дані графіка')
                    ->modalDescription('Це оновить всі періоди, крім введених вручну. Продовжити?')
                    ->action(function () {
                        Artisan::call('donations:collect-chart-data');
                    })
                    ->successNotificationTitle('Дані успішно зібрано'),
            ])
            ->defaultSort('period_type');
    }
}
