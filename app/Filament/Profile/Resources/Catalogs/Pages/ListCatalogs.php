<?php

namespace App\Filament\Profile\Resources\Catalogs\Pages;

use App\Filament\Profile\Resources\Catalogs\CatalogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogs extends ListRecords
{
    protected static string $resource = CatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
