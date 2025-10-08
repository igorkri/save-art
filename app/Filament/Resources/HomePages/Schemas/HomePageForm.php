<?php

namespace App\Filament\Resources\HomePages\Schemas;

use App\Rules\MaxFileSizeRule;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class HomePageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Toggle::make('is_active')
                    ->label('Активна сторінка')
                    ->default(true),

                Section::make('Секція з відео')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('hero_title')
                                ->label('Заголовок героїчної секції')
                                ->required()
                                ->maxLength(255),
                        ]),

                        Grid::make(2)
                            ->schema([
                                FileUpload::make('hero_video_poster')
                                    ->label('Постер відео для десктопів')
                                    ->acceptedFileTypes(['video/mp4'])
                                    ->maxSize(73728) // 72 MB - настойка для в config/livewire.php - 'temporary_file_upload'
                                    ->directory('hero-videos')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    }),

                                FileUpload::make('hero_video_poster_m')
                                    ->label('Постер відео для мобільних')
                                    ->acceptedFileTypes(['video/mp4'])
                                    ->maxSize(73728) // 72 MB - настойка для в config/livewire.php - 'temporary_file_upload'
                                    ->directory('hero-videos')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    }),

                                FileUpload::make('hero_image_poster')
                                    ->label('Постер зображення для десктопів')
                                    ->image()
                                    ->maxSize(5120) // 5 MB
                                    ->directory('hero-images')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->rules([new MaxFileSizeRule(5120)]),
                                FileUpload::make('hero_image_poster_m')
                                    ->label('Постер зображення для мобільних')
                                    ->image()
                                    ->maxSize(5120) // 5 MB
                                    ->directory('hero-images')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->rules([new MaxFileSizeRule(5120)]),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Секція донатів')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('donates_subtitle')
                                ->label('Підзаголовок')
                                ->placeholder('ДОЛУЧАЙТЕСЬ ДО ВІДРОДЖЕННЯ...')
                                ->maxLength(255),

                            TextInput::make('donates_title')
                                ->label('Заголовок')
                                ->placeholder('Твоя підтримка — натхнення для митця')
                                ->maxLength(255)
                                ->required(),

                            RichEditor::make('donates_text')
                                ->label('Опис системи донатів')
                                ->placeholder('Ми пропонуємо прозору систему донатів...')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()->collapsed(),

                Section::make('Статистика')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('total_collected')
                                    ->label('Загальна сума зібраних коштів (₴)')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                TextInput::make('declared_projects')
                                    ->label('Оголошених проєктів')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('active_projects')
                                    ->label('Проєктів в роботі')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                TextInput::make('completed_projects')
                                    ->label('Завершених проєктів')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ]),

                        TextInput::make('sold_projects')
                            ->label('Проданих проєктів')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->collapsible()->collapsed(),

                Section::make('Секція партнерів')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('partners_title')
                                ->label('Заголовок секції партнерів')
                                ->placeholder('Партнери')
                                ->maxLength(255),
                        ]),

                        \Filament\Forms\Components\Repeater::make('partners')
                            ->label('Партнери')
                            ->addActionLabel('+ Додати партнера')
                            ->schema([
                                FileUpload::make('logo')
                                    ->label('Логотип')
                                    ->image()
                                    ->directory('partners')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    }),
                                LanguageTabs::make([
                                    TextInput::make('name')
                                        ->label('Назва')
                                        ->maxLength(255)
                                        ->required(),
                                    TextInput::make('description')
                                        ->label('Короткий опис')
                                        ->maxLength(255)
                                        ->required(),
                                ]),
                            ])
                            ->minItems(0)
                            ->maxItems(10)
                            ->cloneable()
                            ->collapsible(),
                    ])
                    ->collapsible()->collapsed(),

                Section::make('Рекламні блоки')
                    ->schema([
                        Section::make('Перший рекламний блок')
                            ->schema([
                                LanguageTabs::make([
                                    TextInput::make('ad_first_title')
                                        ->label('Заголовок')
                                        ->placeholder('Долучайтесь до Мистецтва Перемоги!')
                                        ->maxLength(255),

                                    TextInput::make('ad_first_button_text')
                                        ->label('Текст кнопки')
                                        ->placeholder('Підтримати платформу')
                                        ->maxLength(100),
                                ]),

                                FileUpload::make('ad_first_image')
                                    ->label('Зображення')
                                    ->image()
                                    ->directory('advertising')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    }),
                            ]),

                        Section::make('Другий рекламний блок')
                            ->schema([
                                LanguageTabs::make([
                                    TextInput::make('ad_second_title')
                                        ->label('Заголовок')
                                        ->placeholder('Ваша допомога та підтримка...')
                                        ->maxLength(255),

                                    TextInput::make('ad_second_button_text')
                                        ->label('Текст кнопки')
                                        ->placeholder('Підтримати митців')
                                        ->maxLength(100),
                                ]),

                                FileUpload::make('ad_second_image')
                                    ->label('Зображення')
                                    ->image()
                                    ->directory('advertising')
                                    ->disk('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    }),
                            ]),
                    ])
                    ->collapsible()->collapsed(),

                Section::make('Футер - Секція для експертів')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('footer_expert_title')
                                ->label('Заголовок')
                                ->placeholder('Запрошуємо експертів до співпраці')
                                ->maxLength(255),

                            Textarea::make('footer_expert_text')
                                ->label('Опис')
                                ->placeholder('Благодійний фонд ID_Art UA відкритий до співпраці...')
                                ->rows(3),

                            Textarea::make('footer_expert_features')
                                ->label('Особливості (JSON масив)')
                                ->placeholder('["Створення сучасного українського мистецтва", "Участь у проведенні виставок", "Популяризація українських митців"]')
                                ->rows(3)
                                ->helperText('Введіть JSON масив з трьома пунктами'),

                            TextInput::make('footer_expert_button_text')
                                ->label('Текст кнопки')
                                ->placeholder('Відправити заявку')
                                ->maxLength(100),
                        ]),
                    ])
                    ->collapsible()->collapsed(),
            ]);
    }
}
