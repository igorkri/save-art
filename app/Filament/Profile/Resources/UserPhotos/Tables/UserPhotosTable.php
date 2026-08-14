<?php

namespace App\Filament\Profile\Resources\UserPhotos\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserPhotosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label(__('profile_user_photos.table.image'))
                    ->disk('public')
                    ->size(80),

                TextColumn::make('likes_count')
                    ->label(__('profile_user_photos.table.likes_count'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('profile_user_photos.table.created_at'))
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
