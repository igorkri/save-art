<?php

namespace App\Filament\Resources\ProfileDocuments\Pages;

use App\Filament\Resources\ProfileDocuments\ProfileDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProfileDocuments extends ListRecords
{
    protected static string $resource = ProfileDocumentResource::class;

    protected static ?string $title = 'Документи профілю';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
