<?php

namespace App\Filament\Resources\ArtistBoards\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

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
                        LanguageTabs::make([
                            TextInput::make('titles.title1')->label('Заголовок 1')->placeholder('Спецпроєкт')->required(),
                            TextInput::make('titles.title2')->label('Заголовок 2')->placeholder('10 художників в 10 національних музеях світу')->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                //                TextInput::make('logo_museums'),
                Section::make('Логотипи музеїв')
                    ->columns(1)
                    ->schema([
                        Repeater::make('logo_museums')
                            ->label('Логотипи музеїв')
                            ->schema([
                                FileUpload::make('logo_museum')
                                    ->label('Логотип музею')
                                    ->image()
                                    ->directory('artist-boards/logos')
                                    ->preserveFilenames()
                                    ->required(),
                            ])
                            ->minItems(1)
                            ->maxItems(10)
                            ->columns(1)
                            ->createItemButtonLabel('Додати логотип музею'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                //                TextInput::make('descriptions'),
                Section::make('Опис')
                    ->columns(1)
                    ->schema([
                        LanguageTabs::make([
                            RichEditor::make('descriptions')->label('Опис')->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
                //                TextInput::make('data'),
                Section::make('Дані артистів та їх робіт')
                    ->columns(1)
                    ->schema([
                        Repeater::make('data')
                            ->label('Дані')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Фото артиста')
                                    ->image()
                                    ->directory('artist-boards/artists')
                                    ->preserveFilenames()
                                    ->required(),
                                LanguageTabs::make([
                                    TextInput::make('name')->label('Ім\'я артиста')->required(),
                                ]),
                                TextInput::make('exhibition_link')->label('Посилання на виставку')->url(),
                                TextInput::make('facebook_link')->label('Посилання на Facebook')->url(),
                                Repeater::make('museums')
                                    ->label('Музеї')
                                    ->schema([
                                        //                                        FileUpload::make('logo')
                                        //                                            ->label('Логотип музею')
                                        //                                            ->image()
                                        //                                            ->directory('artist-boards/museums')
                                        //                                            ->preserveFilenames()
                                        //                                            ->required(),
                                        LanguageTabs::make([
                                            TextInput::make('name')->label('Назва музею')->required(),
                                            TextInput::make('exhibition_name')->label('Назва виставки')->required(),
                                        ]),
                                        TextInput::make('dates')->label('Дати')->required(),
                                    ])
                                    ->columns(1)
                                    ->minItems(1)
                                    ->maxItems(10)
                                    ->cloneable(),
                                Repeater::make('works')
                                    ->label('Роботи')
                                    ->schema([
                                        LanguageTabs::make([
                                            TextInput::make('title')->label('Назва роботи')->required(),
                                            RichEditor::make('description')->label('Опис')->required(),
                                        ]),
                                        FileUpload::make('image')
                                            ->label('Зображення роботи')
                                            ->image()
                                            ->directory('artist-boards/works')
                                            ->preserveFilenames()
                                            ->required(),
                                    ])
                                    ->columns(1)
                                    ->minItems(1)
                                    ->cloneable(),
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
