<?php

namespace App\Filament\Resources\Abouts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AboutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('feats')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('goals')
                    ->required(),
                TextInput::make('tasks')
                    ->required(),
                TextInput::make('implementation')
                    ->required(),
                TextInput::make('results')
                    ->required(),
                TextInput::make('id_art')
                    ->required(),
                TextInput::make('events')
                    ->required(),
                TextInput::make('project')
                    ->required(),
                TextInput::make('artists')
                    ->required(),
                Toggle::make('is_active_artist')
                    ->required(),
                TextInput::make('partners')
                    ->required(),
            ]);
    }
}
