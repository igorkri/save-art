<?php

namespace App\Filament\Resources\ArtCategories\Pages;

use App\Filament\Resources\ArtCategories\ArtCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArtCategory extends EditRecord
{
    protected static string $resource = ArtCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}