<?php

namespace App\Filament\Resources\News\Tables;

use App\Enums\NewsCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('main_image')
                    ->label('Зображення')
                    ->disk('public')
                    ->square(),

                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('category')
                    ->label('Категорія')
                    ->badge()
                    ->formatStateUsing(fn (NewsCategory $state) => $state->getLabel())
                    ->color(fn (NewsCategory $state) => $state === NewsCategory::Event ? 'warning' : 'success')
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Дата публікації')
                    ->dateTime('d.m.Y H:i')
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
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Категорія')
                    ->options(NewsCategory::getOptions()),

                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        '1' => 'Активні',
                        '0' => 'Неактивні',
                    ]),
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
