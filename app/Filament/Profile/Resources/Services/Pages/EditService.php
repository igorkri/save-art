<?php

namespace App\Filament\Profile\Resources\Services\Pages;

use App\Filament\Profile\Resources\Services\ServiceResource;
use App\Models\Team;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($data['serviceable_type'] === Team::class) {
            $data['owner_type'] = 'team';
            $data['team_id'] = $data['serviceable_id'];
        } else {
            $data['owner_type'] = 'personal';
        }

        return $data;
    }
}
