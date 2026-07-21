<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Filament\Resources\Projects\ProjectResource;
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
