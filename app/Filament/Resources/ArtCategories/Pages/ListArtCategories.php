<?php

namespace App\Filament\Resources\ArtCategories\Pages;

use App\Filament\Resources\ArtCategories\ArtCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArtCategories extends ListRecords
{
    protected static string $resource = ArtCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}