<?php

namespace App\Filament\Resources\Messages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Повідомлення')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Користувач')
                            ->relationship('user')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('project_id')
                            ->label('Проєкт')
                            ->relationship('project')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->title ?: 'Без назви')
                            ->searchable()
                            ->preload(),
                        TextInput::make('subject')
                            ->label('Тема')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label('Текст повідомлення')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        Select::make('direction')
                            ->label('Напрямок')
                            ->options([
                                'user_to_admin' => 'Від користувача до адміна',
                                'admin_to_user' => 'Від адміна до користувача',
                            ])
                            ->default('admin_to_user')
                            ->required(),
                        DateTimePicker::make('read_at')
                            ->label('Прочитано'),
                        Hidden::make('admin_id')
                            ->default(fn () => Auth::id()),
                    ]),
            ]);
    }
}
