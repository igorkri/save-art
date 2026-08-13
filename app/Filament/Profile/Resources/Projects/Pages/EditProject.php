<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    use HandlesProjectParameterValuesInForm;

    protected static string $resource = ProjectResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Project || ! $record->exists) {
            return $data;
        }

        return $this->fillProjectParameterValues($data, $record);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractProjectParameterValuesFromData($data);
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Project) {
            $this->syncPendingProjectParameterValues($record);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->status->isEditable()),
        ];
    }
}
