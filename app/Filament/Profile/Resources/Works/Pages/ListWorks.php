<?php

namespace App\Filament\Profile\Resources\Works\Pages;

use App\Filament\Profile\Resources\Projects\Pages\ListProjects;
use App\Filament\Profile\Resources\Works\WorkResource;

class ListWorks extends ListProjects
{
    protected static string $resource = WorkResource::class;
}
