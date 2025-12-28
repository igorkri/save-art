<?php

namespace App\Filament\Resources\ProfileDocuments\Pages;

use App\Filament\Resources\ProfileDocuments\ProfileDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProfileDocument extends EditRecord
{
    protected static string $resource = ProfileDocumentResource::class;

    protected static ?string $title = 'Редагувати документ профілю';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
