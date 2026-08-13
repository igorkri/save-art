<?php

namespace App\Filament\Profile\Resources\UserPhotos\Pages;

use App\Filament\Profile\Resources\UserPhotos\UserPhotoResource;
use App\Models\UserPhoto;
use Filament\Resources\Pages\CreateRecord;

class CreateUserPhoto extends CreateRecord
{
    protected static string $resource = UserPhotoResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['sort_order'] = UserPhoto::query()->where('user_id', auth()->id())->max('sort_order') + 1;

        return $data;
    }
}
