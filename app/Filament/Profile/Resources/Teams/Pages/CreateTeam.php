<?php

namespace App\Filament\Profile\Resources\Teams\Pages;

use App\Filament\Profile\Resources\Teams\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = Str::slug($data['name']['uk']).'-'.Str::random(6);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->teamMembers()->create([
            'user_id' => auth()->id(),
            'role' => 'owner',
            'sort_order' => 0,
        ]);
    }
}
