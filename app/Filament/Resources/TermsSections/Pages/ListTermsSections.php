<?php

namespace App\Filament\Resources\TermsSections\Pages;

use App\Filament\Resources\TermsSections\TermsSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTermsSections extends ListRecords
{
    protected static string $resource = TermsSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
