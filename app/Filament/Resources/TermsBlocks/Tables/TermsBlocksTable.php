<?php

namespace App\Filament\Resources\TermsBlocks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TermsBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('heading')
                    ->label('Заголовок')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['uk'] ?? $state['en'] ?? '-') : ($state ?: '-'))
                    ->searchable()
                    ->limit(60),

                TextColumn::make('section.heading')
                    ->label('Розділ')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['uk'] ?? $state['en'] ?? '-') : ($state ?: '-'))
                    ->sortable(),

                TextColumn::make('list_type')
                    ->label('Список')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'ordered' => 'Нумерований',
                        'unordered' => 'Маркований',
                        default => '—',
                    }),

                TextColumn::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->filters([
                SelectFilter::make('terms_section_id')
                    ->label('Розділ')
                    ->options(function () {
                        return \App\Models\TermsSection::all()
                            ->mapWithKeys(function ($section) {
                                $label = is_array($section->heading)
                                    ? ($section->heading['uk'] ?? $section->heading['en'] ?? "Розділ #{$section->id}")
                                    : ($section->heading ?: "Розділ #{$section->id}");

                                return [$section->id => $label];
                            });
                    })
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
