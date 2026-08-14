<?php

namespace App\Filament\Profile\Resources\Messages\Schemas;

use App\Models\Project;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->label(__('profile_messages.form.project'))
                    ->options(fn () => Project::query()
                        ->where('user_id', auth()->id())
                        ->pluck('title', 'id'))
                    ->searchable(),
                TextInput::make('subject')
                    ->label(__('profile_messages.form.subject'))
                    ->maxLength(255),
                Textarea::make('content')
                    ->label(__('profile_messages.form.content'))
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }
}
