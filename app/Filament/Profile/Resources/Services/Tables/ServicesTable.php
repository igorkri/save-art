<?php

namespace App\Filament\Profile\Resources\Services\Tables;

use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordClasses('profile-service-card-record')
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->columns([
                View::make('filament.profile.services.service-card')
                    ->schema([
                        TextColumn::make('title')
                            ->searchable()
                            ->sortable()
                            ->hidden(),
                        TextColumn::make('description')
                            ->searchable()
                            ->hidden(),
                        TextColumn::make('location')
                            ->searchable()
                            ->hidden(),
                        TextColumn::make('price')
                            ->sortable()
                            ->hidden(),
                        TextColumn::make('created_at')
                            ->sortable()
                            ->hidden(),
                    ]),
            ]);
    }
}
