<?php

namespace App\Filament\Profile\Resources\Teams\Pages;

use App\Filament\Profile\Resources\Teams\TeamResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    /**
     * @return array<Action | DeleteAction>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->icon('heroicon-o-check')
                ->iconPosition(IconPosition::After)
                ->extraAttributes(['class' => 'team-form-primary-action']),

            DeleteAction::make()
                ->icon('heroicon-o-x-mark')
                ->iconPosition(IconPosition::After)
                ->color('primary')
                ->outlined()
                ->extraAttributes(['class' => 'team-form-delete-action']),
        ];
    }

    public function getFormContentComponent(): Component
    {
        return parent::getFormContentComponent()
            ->extraAttributes(['novalidate' => true]);
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Center;
    }
}
