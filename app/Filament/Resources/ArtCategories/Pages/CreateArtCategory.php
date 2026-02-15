<?php

namespace App\Filament\Resources\ArtCategories\Pages;

use App\Filament\Resources\ArtCategories\ArtCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArtCategory extends CreateRecord
{
    protected static string $resource = ArtCategoryResource::class;
}