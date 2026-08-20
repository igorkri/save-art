<?php

namespace App\Filament\Profile\Resources\Teams\Pages;

use App\Filament\Profile\Resources\Teams\TeamResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Str;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = Str::slug($data['name']).'-'.Str::random(6);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->teamMembers()->create([
            'user_id' => auth()->id(),
            'role' => 'owner',
            'sort_order' => 0,
        ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->icon('heroicon-o-check')
                ->iconPosition(IconPosition::After)
                ->extraAttributes(['class' => 'team-form-primary-action']),
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
