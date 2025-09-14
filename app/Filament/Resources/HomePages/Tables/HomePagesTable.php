<?php

namespace App\Filament\Resources\HomePages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomePagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('hero_title')
                    ->label('Героїчний заголовок')
                    ->getStateUsing(fn ($record) => $record->getTranslation('hero_title', 'ua'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->getTranslation('hero_title', 'ua')),

                TextColumn::make('total_collected')
                    ->label('Зібрано коштів')
                    ->money('UAH')
                    ->sortable(),

                TextColumn::make('declared_projects')
                    ->label('Оголошено проєктів')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('active_projects')
                    ->label('Активних проєктів')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
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
            ])
            ->defaultSort('created_at', 'desc');
    }
}
