<?php

namespace App\Filament\Resources\TermsSections\Pages;

use App\Filament\Resources\TermsSections\TermsSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTermsSection extends EditRecord
{
    protected static string $resource = TermsSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
