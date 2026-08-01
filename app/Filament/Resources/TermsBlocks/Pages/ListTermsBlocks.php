<?php

namespace App\Filament\Resources\TermsBlocks\Pages;

use App\Filament\Resources\TermsBlocks\TermsBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTermsBlocks extends ListRecords
{
    protected static string $resource = TermsBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
