<?php

namespace App\Filament\Profile\Resources\Services\Pages;

use App\Filament\Profile\Resources\Services\ServiceResource;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    /**
     * @return array<Action | DeleteAction>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->icon('heroicon-o-check')
                ->iconPosition(IconPosition::After)
                ->extraAttributes(['class' => 'service-form-primary-action']),

            DeleteAction::make()
                ->icon('heroicon-o-x-mark')
                ->iconPosition(IconPosition::After)
                ->color('primary')
                ->outlined()
                ->extraAttributes(['class' => 'service-form-delete-action']),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Center;
    }

    public function getFormContentComponent(): Component
    {
        return parent::getFormContentComponent()
            ->extraAttributes(['novalidate' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($data['serviceable_type'] === Team::class) {
            $data['owner_type'] = 'team';
            $data['team_id'] = $data['serviceable_id'];
        } else {
            $data['owner_type'] = 'personal';
        }

        return $data;
    }
}
