<?php

namespace App\Filament\Profile\Resources\Works\Pages;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Filament\Profile\Resources\Projects\Pages\EditProject;
use App\Filament\Profile\Resources\Works\WorkResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditWork extends EditProject
{
    protected static string $resource = WorkResource::class;

    private string $statusIntent = ProjectStatus::Draft->value;

    protected function getFormActions(): array
    {
        return [
            DeleteAction::make('delete')
                ->label(__('profile_projects.actions.delete_form'))
                ->visible(fn (): bool => $this->getRecord()->canBeDeletedByOwner()),
            Action::make('saveDraft')->label(__('profile_projects.actions.save_draft'))->action('saveDraft'),
            Action::make('publish')->label(__('profile_projects.actions.publish'))->color('primary')->action('publish'),
        ];
    }

    public function saveDraft(): void
    {
        $this->statusIntent = ProjectStatus::Draft->value;
        $this->save();
    }

    public function publish(): void
    {
        $this->statusIntent = ProjectStatus::Moderation->value;
        $this->save();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);
        $data['status'] = $this->statusIntent;
        $data['status_moderation'] = ModerationStatus::Pending->value;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
