<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Назва документа')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->label('Тип документа')
                    ->options(DocumentType::class)
                    ->required(),

                Textarea::make('description')
                    ->label('Опис')
                    ->rows(3)
                    ->maxLength(65535),

                FileUpload::make('file_path')
                    ->label('Файл')
                    ->required()
                    ->disk('public')
                    ->directory('documents')
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('file_name', basename($state));
                        }
                    }),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }
}
