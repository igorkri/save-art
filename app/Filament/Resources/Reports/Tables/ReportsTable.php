<?php

namespace App\Filament\Resources\Reports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label('Обкладинка')
                    ->circular(),

                TextColumn::make('title')
                    ->label('Заголовок')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['uk'] ?? $state['en'] ?? '-') : $state)
                    ->searchable()
                    ->limit(50),

                TextColumn::make('project.title')
                    ->label('Проєкт')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['uk'] ?? $state['en'] ?? '-') : $state)
                    ->searchable()
                    ->limit(30),

                TextColumn::make('user.name')
                    ->label('Автор')
                    ->searchable(),

                TextColumn::make('report_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('collected_amount')
                    ->label('Зібрано')
                    ->money('UAH')
                    ->sortable(),

                TextColumn::make('spent_amount')
                    ->label('Витрачено')
                    ->money('UAH')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Опублікований',
                        'draft' => 'Чернетка',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('report_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Чернетка',
                        'published' => 'Опублікований',
                    ]),

                SelectFilter::make('project_id')
                    ->label('Проєкт')
                    ->relationship('project', 'title')
                    ->searchable()
                    ->preload(),
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
