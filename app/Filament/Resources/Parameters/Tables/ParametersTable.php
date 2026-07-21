<?php

namespace App\Filament\Resources\Parameters\Tables;

use App\Models\ArtCategory;
use App\Models\Parameter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ParametersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->formatStateUsing(fn (Parameter $record): string => $record->getLabel('uk'))
                    ->searchable(),

                TextColumn::make('artCategory.name')
                    ->label('Категорія')
                    ->formatStateUsing(fn ($state, Parameter $record): string => $record->artCategory?->getLabel('uk') ?? '—')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),

                TextColumn::make('values_count')
                    ->label('Значень')
                    ->counts('values')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                SelectFilter::make('art_category_id')
                    ->label('Категорія')
                    ->options(fn () => ArtCategory::query()->orderBy('sort_order')->get()->mapWithKeys(
                        fn (ArtCategory $category) => [$category->id => $category->getLabel('uk')]
                    )->all()),
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
