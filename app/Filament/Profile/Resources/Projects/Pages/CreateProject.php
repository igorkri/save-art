<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Filament\Profile\Resources\Projects\Concerns\HandlesContentBlocksBuilder;
use App\Filament\Profile\Resources\Projects\Concerns\OptimizesStageDocumentImages;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use HandlesContentBlocksBuilder;
    use HandlesProjectParameterValuesInForm;
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
        $data['user_type'] = UserType::Personal->value;
        $data['status'] = ProjectStatus::Draft->value;
        $data['status_moderation'] = ModerationStatus::Pending->value;
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
        }
    }
}
