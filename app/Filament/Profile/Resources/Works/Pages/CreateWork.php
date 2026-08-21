<?php

namespace App\Filament\Profile\Resources\Works\Pages;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Filament\Profile\Resources\Works\WorkResource;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;

class CreateWork extends CreateProject
{
    protected static string $resource = WorkResource::class;

    private string $statusIntent = ProjectStatus::Draft->value;

    public function getFormActionsContentComponent(): Component
    {
        return parent::getFormActionsContentComponent()
            ->extraAttributes(['class' => 'profile-project-work-actions']);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction()->label(__('profile_projects.actions.delete_form')),
            Action::make('saveDraft')->label(__('profile_projects.actions.save_draft'))->action('saveDraft'),
            Action::make('publish')->label(__('profile_projects.actions.publish'))->color('primary')->action('publish'),
        ];
    }

    public function saveDraft(): void
    {
        $this->statusIntent = ProjectStatus::Draft->value;
        $this->create();
    }

    public function publish(): void
    {
        $this->statusIntent = ProjectStatus::Moderation->value;
        $this->create();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data['status'] = $this->statusIntent;
        $data['status_moderation'] = ModerationStatus::Pending->value;

        return $data;
    }
}
