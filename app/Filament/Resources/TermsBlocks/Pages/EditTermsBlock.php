<?php

namespace App\Filament\Resources\TermsBlocks\Pages;

use App\Filament\Resources\TermsBlocks\TermsBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTermsBlock extends EditRecord
{
    protected static string $resource = TermsBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
