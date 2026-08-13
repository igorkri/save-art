<?php

namespace App\Filament\Profile\Resources\UserPhotos\Pages;

use App\Filament\Profile\Resources\UserPhotos\UserPhotoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserPhotos extends ListRecords
{
    protected static string $resource = UserPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
