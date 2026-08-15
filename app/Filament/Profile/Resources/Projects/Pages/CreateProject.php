<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Filament\Profile\Resources\Projects\Concerns\HandlesContentBlocksBuilder;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Concerns\HandlesProjectParameterValuesInForm;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use HandlesContentBlocksBuilder;
    use HandlesProjectParameterValuesInForm;

    protected static string $resource = ProjectResource::class;

    /**
     * Жива валідація полів, позначених ->live(onBlur: true) у ProjectForm
     * (наприклад, title, budget_goal) — помилка показується одразу після
     * втрати фокусу полем, а не тільки при переході між кроками візарда
     * чи фінальному збереженні.
     */
    public function updated(string $name): void
    {
        if (! str($name)->startsWith('data.')) {
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
