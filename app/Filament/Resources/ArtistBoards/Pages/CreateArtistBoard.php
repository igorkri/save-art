<?php

namespace App\Filament\Resources\ArtistBoards\Pages;

use App\Filament\Resources\ArtistBoards\ArtistBoardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArtistBoard extends CreateRecord
{
    protected static string $resource = ArtistBoardResource::class;
}
