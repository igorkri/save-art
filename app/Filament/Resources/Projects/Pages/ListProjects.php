<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Канбан')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(fn (): string => ProjectResource::getUrl('kanban')),
            CreateAction::make(),
        ];
    }
}
