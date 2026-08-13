<?php

namespace App\Filament\Profile\Resources\UserPhotos\Pages;

use App\Filament\Profile\Resources\UserPhotos\UserPhotoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserPhoto extends EditRecord
{
    protected static string $resource = UserPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
