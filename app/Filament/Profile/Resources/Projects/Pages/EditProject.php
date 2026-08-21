<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Filament\Profile\Resources\Projects\Concerns\HandlesContentBlocksBuilder;
use App\Filament\Profile\Resources\Projects\Concerns\OpensProjectPreview;
use App\Filament\Profile\Resources\Projects\Concerns\OptimizesStageDocumentImages;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Models\Project;
use App\Services\ProjectWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditProject extends EditRecord
{
    use HandlesContentBlocksBuilder;
    use HandlesProjectParameterValuesInForm;
    use OpensProjectPreview;
    use OptimizesStageDocumentImages;

    protected static string $resource = ProjectResource::class;

    /**
     * Знімок project_stages.documents ДО збереження (ключ — id етапу), щоб
     * у afterSave() відрізнити щойно завантажені фото від уже оброблених
     * і не ганяти в чергу оптимізацію одних і тих самих файлів щоразу.
     *
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $stageDocumentsBeforeSave = [];

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

        $data['content_blocks'] = $this->contentBlocksToBuilderFormat($record->content_blocks);
        $data['final_result'] = $this->contentBlocksToBuilderFormat($this->finalResultBlocks($record));

        return $this->fillProjectParameterValues($data, $record);
    }

    /**
     * final_result історично зберігався і як список блоків (сучасний формат,
     * сумісний з art-ua-info), і як єдиний об'єкт {type, url, description}
     * (застарілий формат save-art). Білдер у формі розуміє лише список блоків —
     * старий формат просто не показуємо, щоб не зламати рендер.
     *
     * @return array<int, array<string, mixed>>
     */
    private function finalResultBlocks(Project $record): array
    {
        $finalResult = $record->final_result;

        return is_array($finalResult) && array_is_list($finalResult) ? $finalResult : [];
    }

    /**
     * Filament зберігає ->relationship()-репітери (stages) всередині
     * form->getState(), яке виконується ДО mutateFormDataBeforeSave — тобто
     * знімок "до" там уже запізнюється. Хук beforeSave() — єдина точка, що
     * гарантовано спрацьовує раніше за це збереження зв'язків.
     */
    protected function beforeSave(): void
    {
        $this->stageDocumentsBeforeSave = $this->getRecord()->stages()->get(['id', 'documents'])
            ->pluck('documents', 'id')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if ($record->status === ProjectStatus::Announced
            && array_key_exists('budget_goal', $data)
            && (float) $data['budget_goal'] < (float) $record->budget_goal) {
            throw ValidationException::withMessages([
                'data.budget_goal' => 'Для оголошеного проєкту бюджет можна лише збільшувати.',
            ]);
        }

        if ($record->status === ProjectStatus::Moderation
            && $record->status_moderation === ModerationStatus::Pending) {
            $data['status'] = ProjectStatus::Draft->value;
            $data['status_moderation'] = ModerationStatus::Pending->value;
        }

        $data['content_blocks'] = $this->contentBlocksFromBuilderFormat($data['content_blocks'] ?? null);

        // Крок "Фінальний результат" видимий лише для проєктів у роботі/завершених
        // (->visible() у ProjectForm) — на чернетці/модерації ключа 'final_result'
        // у стані форми взагалі немає, і перезаписувати БД порожнім масивом не треба.
        if (array_key_exists('final_result', $data)) {
            $data['final_result'] = $this->contentBlocksFromBuilderFormat($data['final_result']);
        }

        return $this->extractProjectParameterValuesFromData($data);
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Project) {
            $this->syncPendingProjectParameterValues($record);
            $this->dispatchOptimizationForNewStageDocuments($record->fresh(['stages']), $this->stageDocumentsBeforeSave);
        }
    }

    public function previewProject(): void
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $this->openProjectPreview($this->getRecord()->fresh());
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->submitForModerationAction(),
            $this->pauseAction(),
            $this->resumeAction(),
            $this->completeAction(),
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->canBeDeletedByOwner()),
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
            ->visible(fn (): bool => $this->getRecord()->status === ProjectStatus::Draft)
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

    /**
     * Митець сам ставить збір на паузу (Announced/InProgress → Paused). Технічно
     * API вже дозволяв це власнику проєкту (MyProjectController::pause()) — тут
     * лише додаємо той самий перехід у кабінет через ProjectWorkflowService.
     */
    private function pauseAction(): Action
    {
        return Action::make('pause')
            ->label(__('profile_projects.actions.pause'))
            ->icon('heroicon-o-pause-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('profile_projects.actions.pause_heading'))
            ->modalDescription(__('profile_projects.actions.pause_description'))
            ->visible(fn (): bool => in_array($this->getRecord()->status, [ProjectStatus::Announced, ProjectStatus::InProgress], true))
            ->action(function (): void {
                $project = $this->getRecord();

                if (app(ProjectWorkflowService::class)->pause($project)) {
                    Notification::make()
                        ->title(__('profile_projects.actions.pause_success'))
                        ->success()
                        ->send();

                    $this->fillForm();

                    return;
                }

                Notification::make()
                    ->title(__('profile_projects.actions.pause_failed'))
                    ->danger()
                    ->send();
            });
    }

    /**
     * Митець відновлює призупинений збір (Paused → InProgress/Announced,
     * залежно від зібраного бюджету — вирішує сам ProjectWorkflowService::resume()).
     */
    private function resumeAction(): Action
    {
        return Action::make('resume')
            ->label(__('profile_projects.actions.resume'))
            ->icon('heroicon-o-play-circle')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('profile_projects.actions.resume_heading'))
            ->modalDescription(__('profile_projects.actions.resume_description'))
            ->visible(fn (): bool => $this->getRecord()->status === ProjectStatus::Paused)
            ->action(function (): void {
                $project = $this->getRecord();

                if (app(ProjectWorkflowService::class)->resume($project)) {
                    Notification::make()
                        ->title(__('profile_projects.actions.resume_success'))
                        ->success()
                        ->send();

                    $this->fillForm();

                    return;
                }

                Notification::make()
                    ->title(__('profile_projects.actions.resume_failed'))
                    ->danger()
                    ->send();
            });
    }
}
