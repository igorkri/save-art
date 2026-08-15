<?php

namespace App\Filament\Resources\ArtistBoards\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ArtistBoardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ArtistBoard')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Загальне')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Назви')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('titles.title1')->label('Заголовок 1')->placeholder('Спецпроєкт')->required(),
                                        TextInput::make('titles.title2')->label('Заголовок 2')->placeholder('10 художників в 10 національних музеях світу')->required(),
                                        Textarea::make('titles.description')->label('Короткий опис')->rows(2)->columnSpanFull(),
                                    ]),

                                Section::make('Опис')
                                    ->schema([
                                        RichEditor::make('descriptions')->label('Опис')->required(),
                                    ]),

                                Section::make('Логотипи музеїв')
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
                                    ]),
                            ]),

                        Tab::make('Артисти та роботи')
                            ->icon(Heroicon::OutlinedUserGroup)
                            ->schema([
                                Repeater::make('data')
                                    ->label('Артисти')
                                    ->createItemButtonLabel('Додати артиста')
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Новий артист')
                                    ->collapsible()
                                    ->collapsed()
                                    ->cloneable()
                                    ->schema([
                                        Tabs::make('Artist')
                                            ->tabs([
                                                Tab::make('Основне')
                                                    ->icon(Heroicon::OutlinedIdentification)
                                                    ->schema([
                                                        Grid::make(6)
                                                            ->schema([
                                                                FileUpload::make('image')
                                                                    ->label('Фото артиста')
                                                                    ->image()
                                                                    ->disk('public')
                                                                    ->directory('artist-boards/artists')
                                                                    ->preserveFilenames()
                                                                    ->avatar()
                                                                    ->required()
                                                                    ->columnSpan(1),
                                                                Grid::make(1)
                                                                    ->columnSpan(5)
                                                                    ->schema([
                                                                        TextInput::make('name')->label('Ім\'я артиста')->required(),
                                                                        TextInput::make('exhibition_link')->label('Посилання на виставку')->url(),
                                                                        TextInput::make('facebook_link')->label('Посилання на Facebook')->url(),
                                                                    ]),
                                                            ]),
                                                    ]),

                                                Tab::make('Музеї')
                                                    ->icon(Heroicon::OutlinedBuildingLibrary)
                                                    ->schema([
                                                        Repeater::make('museums')
                                                            ->label('Музеї')
                                                            ->schema([
                                                                Grid::make(3)
                                                                    ->schema([
                                                                        TextInput::make('name')->label('Назва музею'),
                                                                        TextInput::make('exhibition_name')->label('Назва виставки'),
                                                                        DateRangePicker::make('dates')
                                                                            ->label('Дати')
                                                                            ->format('d.m.Y')
                                                                            ->placeholder('01.01.2024 - 01.03.2024'),
                                                                    ]),
                                                            ])
                                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                                            ->maxItems(10)
                                                            ->cloneable(),
                                                    ]),

                                                Tab::make('Роботи')
                                                    ->icon(Heroicon::OutlinedPhoto)
                                                    ->schema([
                                                        Repeater::make('works')
                                                            ->label('Роботи')
                                                            ->schema([
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextInput::make('title')->label('Назва роботи')->required(),
                                                                        FileUpload::make('image')
                                                                            ->label('Зображення роботи')
                                                                            ->image()
                                                                            ->disk('public')
                                                                            ->directory('artist-boards/works')
                                                                            ->preserveFilenames()
                                                                            ->required(),
                                                                        RichEditor::make('description')->label('Опис')->columnSpanFull(),
                                                                    ]),
                                                            ])
                                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                                            ->minItems(1)
                                                            ->cloneable(),
                                                    ]),
                                            ]),
                                    ])
                                    ->minItems(1)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
