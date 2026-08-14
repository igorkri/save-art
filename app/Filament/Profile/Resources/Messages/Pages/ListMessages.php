<?php

namespace App\Filament\Profile\Resources\Messages\Pages;

use App\Filament\Profile\Resources\Messages\MessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('profile_messages.actions.compose')),
        ];
    }
}
