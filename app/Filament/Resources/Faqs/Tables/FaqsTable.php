<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label('Питання')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['uk'] ?? $state['en'] ?? '-') : $state)
                    ->searchable()
                    ->limit(60),

                TextColumn::make('category.name')
                    ->label('Категорія')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['uk'] ?? $state['en'] ?? '-') : $state)
                    ->sortable(),

                TextColumn::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активне')
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
                SelectFilter::make('faq_category_id')
                    ->label('Категорія')
                    ->options(function () {
                        return \App\Models\FaqCategory::all()
                            ->mapWithKeys(function ($category) {
                                $label = is_array($category->name)
                                    ? ($category->name['uk'] ?? $category->name['en'] ?? 'Без назви')
                                    : $category->name;

                                return [$category->id => $label];
                            });
                    })
                    ->searchable()
                    ->preload(),

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
