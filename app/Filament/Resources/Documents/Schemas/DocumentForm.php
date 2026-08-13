<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Основна інформація')
                            ->description('Назва та опис документа')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Назва')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Введіть назву документа')
                                    ->columnSpanFull(),

                                RichEditor::make('description')
                                    ->label('Опис')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        Section::make('Параметри')
                            ->schema([
                                Select::make('type')
                                    ->label('Тип документа')
                                    ->options(DocumentType::class)
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),

                                FileUpload::make('file_path')
                                    ->label('Файл')
                                    ->required()
                                    ->disk('public')
                                    ->directory('documents')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->maxSize(10240)
                                    ->hint('Максимум 10 МБ')
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
