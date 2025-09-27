<?php

namespace App\Filament\Resources\HomePages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomePagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hero_video_poster')
                    ->searchable(),
                TextColumn::make('hero_video_url')
                    ->searchable(),
                TextColumn::make('total_collected')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('declared_projects')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('active_projects')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('completed_projects')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sold_projects')
                    ->numeric()
                    ->sortable(),
                ImageColumn::make('ad_first_image'),
                ImageColumn::make('ad_second_image'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
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
            ]);
    }
}
