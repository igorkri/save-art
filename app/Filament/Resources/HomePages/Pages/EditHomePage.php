<?php

namespace App\Filament\Resources\HomePages\Pages;

use App\Filament\Resources\HomePages\HomePageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomePage extends EditRecord
{
    protected static string $resource = HomePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
