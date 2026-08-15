<?php

namespace App\Filament\Resources\ArtistBoards\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArtistBoardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                //                TextInput::make('titles'),
                Section::make('Назви')
                    ->columns(1)
                    ->schema([
                        TextInput::make('titles.title1')->label('Заголовок 1')->placeholder('Спецпроєкт')->required(),
                        TextInput::make('titles.title2')->label('Заголовок 2')->placeholder('10 художників в 10 національних музеях світу')->required(),
                        Textarea::make('titles.description')->label('Короткий опис')->rows(2),
                    ])
                    ->collapsible(),

                //                TextInput::make('logo_museums'),
                Section::make('Логотипи музеїв')
                    ->columns(1)
                    ->schema([
                        FileUpload::make('logo_museums')
                            ->label('Логотипи музеїв')
                            ->image()
                            ->disk('public')
                            ->directory('artist-boards/logos')
                            ->preserveFilenames()
                            ->multiple()
                            ->panelLayout('grid')
                            ->imagePreviewHeight('100')
                            ->extraAttributes(['class' => 'fi-logo-museums-grid'])
                            ->reorderable()
                            ->minFiles(1)
                            ->maxFiles(10)
                            ->required(),
                    ])
                    ->collapsible(),
                //                TextInput::make('descriptions'),
                Section::make('Опис')
                    ->columns(1)
                    ->schema([
                        RichEditor::make('descriptions')->label('Опис')->required(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                //                TextInput::make('data'),
                Section::make('Дані артистів та їх робіт')
                    ->columns(1)
                    ->schema([
                        Repeater::make('data')
                            ->label('Дані')
                            ->createItemButtonLabel('Додати артиста')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Фото артиста')
                                    ->image()
                                    ->disk('public')
                                    ->directory('artist-boards/artists')
                                    ->preserveFilenames()
                                    ->required(),
                                TextInput::make('name')->label('Ім\'я артиста')->required(),
                                TextInput::make('exhibition_link')->label('Посилання на виставку')->url(),
                                TextInput::make('facebook_link')->label('Посилання на Facebook')->url(),

                                Section::make('Музей')
                                    ->schema([
                                        Repeater::make('museums')
                                            ->label('Музеї')
                                            ->schema([
                                                TextInput::make('name')->label('Назва музею'),
                                                TextInput::make('exhibition_name')->label('Назва виставки'),
                                                TextInput::make('dates')->label('Дати')->placeholder('01.01.2024 - 01.03.2024'),
                                            ])
                                            ->columns(1)
//                                            ->minItems(0)
                                            ->maxItems(10)
                                            ->cloneable(),

                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                Section::make('Роботи артиста')
                                    ->schema([
                                        Repeater::make('works')
                                            ->label('Роботи')
                                            ->schema([
                                                TextInput::make('title')->label('Назва роботи')->required(),
                                                RichEditor::make('description')->label('Опис'),
                                                FileUpload::make('image')
                                                    ->label('Зображення роботи')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('artist-boards/works')
                                                    ->preserveFilenames()
                                                    ->required(),
                                            ])
                                            ->columns(1)
                                            ->minItems(1)
                                            ->cloneable(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ])
                            ->columns(1)
                            ->minItems(1)
                            ->required(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
