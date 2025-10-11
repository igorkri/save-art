<?php

namespace App\Filament\Resources\ArtistBoards\Pages;

use App\Filament\Resources\ArtistBoards\ArtistBoardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArtistBoard extends EditRecord
{
    protected static string $resource = ArtistBoardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
