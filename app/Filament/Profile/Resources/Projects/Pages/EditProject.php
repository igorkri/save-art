<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Models\Project;
use App\Services\ProjectWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
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
            $this->submitForModerationAction(),
            $this->completeAction(),
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->status->isEditable()),
        ];
    }

    /**
     * Митець вручну подає чернетку на модерацію (docs/project-lifecycle-flow.md).
     */
    private function submitForModerationAction(): Action
    {
        return Action::make('submitForModeration')
            ->label(__('profile_projects.actions.submit_for_moderation'))
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('profile_projects.actions.submit_for_moderation_heading'))
            ->modalDescription(__('profile_projects.actions.submit_for_moderation_description'))
            ->visible(fn (): bool => $this->getRecord()->status->isEditable())
            ->action(function (): void {
                $project = $this->getRecord();

                if (app(ProjectWorkflowService::class)->submitForModeration($project)) {
                    Notification::make()
                        ->title(__('profile_projects.actions.submit_for_moderation_success'))
                        ->success()
                        ->send();

                    $this->fillForm();

                    return;
                }

                Notification::make()
                    ->title(__('profile_projects.actions.submit_for_moderation_failed'))
                    ->danger()
                    ->send();
            });
    }

    /**
     * Митець вручну завершує проєкт, який у роботі, після здачі фінального
     * результату (docs/project-lifecycle-flow.md). Завантаження фінального
     * результату відбувається поза цією панеллю — тут лише сам перехід статусу.
     */
    private function completeAction(): Action
    {
        return Action::make('complete')
            ->label(__('profile_projects.actions.complete'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('profile_projects.actions.complete_heading'))
            ->modalDescription(__('profile_projects.actions.complete_description'))
            ->visible(fn (): bool => $this->getRecord()->status === ProjectStatus::InProgress)
            ->action(function (): void {
                $project = $this->getRecord();

                if (blank($project->final_result)) {
                    Notification::make()
                        ->title(__('profile_projects.actions.complete_missing_final_result'))
                        ->warning()
                        ->send();

                    return;
                }

                if (app(ProjectWorkflowService::class)->complete($project)) {
                    Notification::make()
                        ->title(__('profile_projects.actions.complete_success'))
                        ->success()
                        ->send();

                    $this->fillForm();

                    return;
                }

                Notification::make()
                    ->title(__('profile_projects.actions.complete_failed'))
                    ->danger()
                    ->send();
            });
    }
}
