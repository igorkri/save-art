<?php

namespace App\Filament\Resources\ArtistBoards\Pages;

use App\Filament\Resources\ArtistBoards\ArtistBoardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArtistBoards extends ListRecords
{
    protected static string $resource = ArtistBoardResource::class;

    protected static ?string $title = 'Дошка художників';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
