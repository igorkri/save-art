<?php

namespace App\Filament\Profile\Resources\Messages\Pages;

use App\Filament\Profile\Resources\Messages\MessageResource;
use App\Models\Message;
use Filament\Resources\Pages\CreateRecord;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['direction'] = Message::DIRECTION_USER_TO_ADMIN;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
