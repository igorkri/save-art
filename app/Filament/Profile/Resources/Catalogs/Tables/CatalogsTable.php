<?php

namespace App\Filament\Profile\Resources\Catalogs\Tables;

use App\Models\ArtCatalog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CatalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->label(__('profile_catalogs.table.image'))
                    ->disk('public')
                    ->size(80),

                TextColumn::make('title')
                    ->label(__('profile_catalogs.table.title'))
                    ->formatStateUsing(function (mixed $state): string {
                        if (is_string($state)) {
                            $state = json_decode($state, true) ?? [];
                        }

                        return $state['uk'] ?? $state['en'] ?? '';
                    })
                    ->searchable(false),

                TextColumn::make('artCategory.name')
                    ->label(__('profile_catalogs.table.category')),

                TextColumn::make('published_at')
                    ->label(__('profile_catalogs.table.published_at'))
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('likes_count')
                    ->label(__('profile_catalogs.table.likes_count'))
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_primary')
                    ->label(__('profile_catalogs.table.is_primary'))
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('setPrimary')
                    ->label(__('profile_catalogs.actions.set_primary'))
                    ->icon('heroicon-o-star')
                    ->visible(fn (ArtCatalog $record): bool => ! $record->is_primary)
                    ->action(function (ArtCatalog $record): void {
                        ArtCatalog::query()
                            ->where('user_id', $record->user_id)
                            ->where('id', '!=', $record->id)
                            ->update(['is_primary' => false]);

                        $record->update(['is_primary' => true]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
