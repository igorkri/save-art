<?php

namespace App\Filament\Resources\HomePages\Pages;

use App\Filament\Resources\HomePages\HomePageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomePages extends ListRecords
{
    protected static string $resource = HomePageResource::class;

    protected static ?string $title = 'Головна сторінка';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
