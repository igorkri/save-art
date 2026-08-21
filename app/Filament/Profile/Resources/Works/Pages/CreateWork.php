<?php

namespace App\Filament\Profile\Resources\Works\Pages;

use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Filament\Profile\Resources\Works\WorkResource;

class CreateWork extends CreateProject
{
    protected static string $resource = WorkResource::class;
}
