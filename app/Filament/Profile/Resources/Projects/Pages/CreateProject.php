<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use HandlesProjectParameterValuesInForm;

    protected static string $resource = ProjectResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['user_type'] = UserType::Personal->value;
        $data['status'] = ProjectStatus::Draft->value;
        $data['status_moderation'] = ModerationStatus::Pending->value;

        return $this->extractProjectParameterValuesFromData($data);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record instanceof Project) {
            $this->syncPendingProjectParameterValues($record);
        }
    }
}
