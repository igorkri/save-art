<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Інформація про звіт')
                    ->columnSpan(2)
                    ->schema([
                        Select::make('project_id')
                            ->label('Проєкт')
                            ->relationship('project', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->label('Автор')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('report_date')
                            ->label('Дата звіту')
                            ->required()
                            ->default(now()),

                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Чернетка',
                                'published' => 'Опублікований',
                            ])
                            ->default('published')
                            ->required(),
                    ]),

                Section::make('Контент')
                    ->columnSpan(2)
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('title')
                                ->label('Заголовок')
                                ->required()
                                ->maxLength(255),
                            RichEditor::make('description')
                                ->label('Опис'),
                        ])->columnSpanFull(),

                        FileUpload::make('cover')
                            ->label('Обкладинка')
                            ->image()
                            ->imageEditor()
                            ->directory('reports/covers'),

                        FileUpload::make('images')
                            ->label('Галерея зображень')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->directory('reports/images')
                            ->reorderable(),

                        FileUpload::make('attachments')
                            ->label('Прикріплені файли')
                            ->multiple()
                            ->directory('reports/attachments')
                            ->preserveFilenames(),
                    ]),

                Section::make('Фінансова інформація')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('collected_amount')
                            ->label('Зібрано (грн)')
                            ->numeric()
                            ->default(0)
                            ->prefix('₴'),

                        TextInput::make('spent_amount')
                            ->label('Витрачено (грн)')
                            ->numeric()
                            ->default(0)
                            ->prefix('₴'),
                    ]),
            ]);
    }
}
