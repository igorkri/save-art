<?php

namespace App\Filament\Resources\Parameters\Tables;

use App\Models\ArtCategory;
use App\Models\Parameter;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParametersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(
                fn ($livewire): string => filled($livewire->tableFilters['art_category_id']['value'] ?? null)
                    ? 'Перетягніть рядки, щоб змінити порядок характеристик у цій категорії.'
                    : 'Щоб змінити порядок характеристик (перетягуванням), спершу оберіть одну категорію у фільтрі.'
            )
            ->columns([

                TextColumn::make('artCategory.name')
                    ->label('Категорія')
                    ->getStateUsing(fn (Parameter $record): string => $record->artCategory?->getLabel('uk') ?? '—')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Назва')
                    ->getStateUsing(fn (Parameter $record): string => $record->getLabel('uk'))
                    ->searchable(),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            // Ручне перетягування (drag&drop) змінює sort_order по всій таблиці,
            // тому має сенс лише коли характеристики відфільтровані до однієї
            // категорії — інакше порядок різних категорій перемішається.
            ->reorderable(
                'sort_order',
                fn ($livewire): bool => filled($livewire->tableFilters['art_category_id']['value'] ?? null),
            )
            ->filters([
                Filter::make('art_category_id')
                    ->label('Категорія')
                    ->schema([
                        SelectTree::make('value')
                            ->label('Категорія')
                            ->query(ArtCategory::query(), 'title', 'parent_id')
                            ->searchable()
                            ->placeholder('Усі категорії'),
                    ])
                    ->query(
                        fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                            ? $query->where('art_category_id', $data['value'])
                            : $query
                    )
                    ->indicateUsing(function (array $data): array {
                        if (blank($data['value'] ?? null)) {
                            return [];
                        }

                        $label = ArtCategory::find($data['value'])?->getLabel('uk') ?? $data['value'];

                        return [Indicator::make('Категорія: '.$label)];
                    }),
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
