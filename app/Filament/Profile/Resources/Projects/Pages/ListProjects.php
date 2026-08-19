<?php

namespace App\Filament\Profile\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('profile_projects.tabs.list.all')),
            'drafts' => Tab::make(__('profile_projects.tabs.list.drafts'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn(
                    'status',
                    array_map(fn (ProjectStatus $status) => $status->value, ProjectStatus::privateStatuses())
                )),
        ];
    }
}
