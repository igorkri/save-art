<?php

namespace App\Filament\Profile\Resources\Services\Pages;

use App\Filament\Profile\Resources\Services\ServiceResource;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Str;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['owner_type'] ?? 'personal') === 'team') {
            $data['serviceable_type'] = Team::class;
            $data['serviceable_id'] = $data['team_id'];
        } else {
            $data['serviceable_type'] = User::class;
            $data['serviceable_id'] = auth()->id();
        }
        unset($data['owner_type'], $data['team_id']);

        $data['slug'] = $this->generateUniqueSlug($data['title'] ?? Str::random(8));

        return $data;
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (Service::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
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
                ->extraAttributes(['class' => 'service-form-primary-action']),
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
}
