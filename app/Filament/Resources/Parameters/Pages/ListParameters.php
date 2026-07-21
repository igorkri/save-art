<?php

namespace App\Filament\Resources\Parameters\Pages;

use App\Filament\Resources\Parameters\ParameterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParameters extends ListRecords
{
    protected static string $resource = ParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
