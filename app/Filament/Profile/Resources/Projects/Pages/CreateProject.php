<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Enums\ModerationStatus;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Filament\Profile\Resources\Projects\Concerns\HandlesContentBlocksBuilder;
use App\Filament\Profile\Resources\Projects\Concerns\OpensProjectPreview;
use App\Filament\Profile\Resources\Projects\Concerns\OptimizesStageDocumentImages;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;

class CreateProject extends CreateRecord
{
    use HandlesContentBlocksBuilder;
    use HandlesProjectParameterValuesInForm;
    use OpensProjectPreview;
    use OptimizesStageDocumentImages;

    protected static string $resource = ProjectResource::class;

    /**
     * Live-поля з ->live(onBlur: true) у ProjectForm, для яких потрібна
     * миттєва валідація без очікування кроку/сабміту. Навмисно білий
     * список (а не всі "data.*"), бо валідація довільних шляхів усередині
     * Repeater/FileUpload (наприклад, файл документа етапу) веде до
     * помилки — Filament очікує там інший формат значення для validateOnly.
     */
    private const LIVE_VALIDATED_FIELDS = ['data.title', 'data.budget_goal'];

    private bool $shouldOpenPreviewAfterCreate = false;

    public function getFormActionsContentComponent(): Component
    {
        return parent::getFormActionsContentComponent()
            ->extraAttributes(['class' => 'profile-project-create-actions']);
    }

    public function updated(string $name): void
    {
        if (! in_array($name, self::LIVE_VALIDATED_FIELDS, true)) {
            return;
        }

        $this->validateOnly($name);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['user_type'] = $data['team_id'] ?? null
            ? UserType::Team->value
            : (($data['is_legal'] ?? false) ? UserType::Legal->value : UserType::Personal->value);
        $data['is_legal'] = $data['user_type'] === UserType::Legal->value;
        $data['status'] = ProjectStatus::Draft->value;
        $data['status_moderation'] = ModerationStatus::Pending->value;
        // Усі проєкти, створені у спільній Filament-панелі "profile", вважаються
        // такими, що прийшли з art-ua-info (save-art власного кабінету не має).
        $data['source'] = ProjectSource::ArtUaInfo->value;
        $data['content_blocks'] = $this->contentBlocksFromBuilderFormat($data['content_blocks'] ?? null);

        // Крок "Фінальний результат" на створенні завжди прихований (новий проєкт
        // завжди Draft) — ключа 'final_result' у стані форми немає.
        if (array_key_exists('final_result', $data)) {
            $data['final_result'] = $this->contentBlocksFromBuilderFormat($data['final_result']);
        }

        return $this->extractProjectParameterValuesFromData($data);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record instanceof Project) {
            $this->syncPendingProjectParameterValues($record);
            $this->dispatchOptimizationForAllStageDocuments($record->fresh(['stages']));

            if ($this->shouldOpenPreviewAfterCreate) {
                $this->openProjectPreview($record->fresh());
            }
        }
    }

    public function previewProject(): void
    {
        $this->shouldOpenPreviewAfterCreate = true;

        $this->create();
    }

    protected function getRedirectUrl(): string
    {
        if (! $this->shouldOpenPreviewAfterCreate || ! $this->record instanceof Project) {
            return parent::getRedirectUrl();
        }

        return ProjectResource::getUrl('edit', [
            'record' => $this->record,
            'step' => request()->query('step'),
        ]);
    }
}
