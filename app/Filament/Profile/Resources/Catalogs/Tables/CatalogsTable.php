<?php

namespace App\Filament\Profile\Resources\Catalogs\Tables;

use App\Filament\Profile\Resources\Catalogs\CatalogResource;
use App\Models\ArtCatalog;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CatalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordClasses('profile-catalog-card-record')
            ->recordUrl(fn (ArtCatalog $record): string => CatalogResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                View::make('filament.profile.catalogs.catalog-card')
                    ->schema([
                        TextColumn::make('title')->searchable()->hidden(),
                        TextColumn::make('artCategory.name')->searchable()->hidden(),
                        TextColumn::make('published_at')->sortable()->hidden(),
                        TextColumn::make('likes_count')->sortable()->hidden(),
                    ]),
            ]);
    }
}
