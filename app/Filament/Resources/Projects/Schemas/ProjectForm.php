<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ArtCategory;
use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Enums\UserType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                // Ліва колонка - основна інформація
                Tabs::make('Основна інформація')
                    ->columnSpan(2)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make('Загальне')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Select::make('user_id')
                                    ->label('Автор')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('user_type')
                                    ->label('Тип власника')
                                    ->options(UserType::getOptions())
                                    ->default(UserType::Personal->value)
                                    ->required(),

                                LanguageTabs::make([
                                    TextInput::make('title')
                                        ->label('Назва проєкту')
                                        ->required()
                                        ->maxLength(255),
                                    Textarea::make('short_description')
                                        ->label('Короткий опис')
                                        ->rows(3),
                                ])->columnSpanFull(),

                                FileUpload::make('cover')
                                    ->label('Обкладинка')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('projects/covers')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Категорія')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Select::make('art_category')
                                    ->label('Галузь мистецтва')
                                    ->options(ArtCategory::getOptions())
                                    ->reactive()
                                    ->required(),

                                Select::make('art_subcategory')
                                    ->label('Підкатегорія')
                                    ->options(function (callable $get) {
                                        $category = $get('art_category');
                                        if (! $category) {
                                            return [];
                                        }
                                        $artCategory = ArtCategory::tryFrom($category);

                                        return $artCategory?->getSubcategories() ?? [];
                                    })
                                    ->visible(fn (callable $get) => filled($get('art_category'))),

                                LanguageTabs::make([
                                    TextInput::make('tags')
                                        ->label('Теги (через кому)'),
                                ])->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Бюджет')
                            ->icon('heroicon-o-currency-dollar')
                            ->columns(2)
                            ->schema([
                                Select::make('currency')
                                    ->label('Валюта')
                                    ->options([
                                        Currency::UAH->value => '₴ UAH',
                                        Currency::USD->value => '$ USD',
                                        Currency::EUR->value => '€ EUR',
                                    ])
                                    ->default(Currency::UAH->value)
                                    ->required(),

                                TextInput::make('budget_goal')
                                    ->label('Ціль збору')
                                    ->numeric()
                                    ->prefix(fn (callable $get) => match ($get('currency')) {
                                        'USD' => '$',
                                        'EUR' => '€',
                                        default => '₴',
                                    })
                                    ->required(),

                                TextInput::make('budget_collected')
                                    ->label('Зібрано')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),

                                TextInput::make('estimated_days')
                                    ->label('Орієнтовна кількість днів')
                                    ->numeric()
                                    ->minValue(1),

                                Repeater::make('budget_items')
                                    ->label('Статті бюджету')
                                    ->schema([
                                        LanguageTabs::make([
                                            TextInput::make('name')
                                                ->label('Назва')
                                                ->required(),
                                        ])->columnSpan(1),

                                        TextInput::make('amount')
                                            ->label('Сума')
                                            ->numeric()
                                            ->required()
                                            ->prefix(fn (callable $get) => match ($get('currency')) {
                                                'USD' => '$',
                                                'EUR' => '€',
                                                default => '₴',
                                            }),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Характеристики')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Repeater::make('characteristics')
                                    ->label('Характеристики проєкту')
                                    ->schema([

                                        LanguageTabs::make([
                                            TextInput::make('name')
                                                ->label('Назва')
                                                ->placeholder('Тривалість, Жанр, Режисер...')
                                                ->required(),
                                        ])->columnSpan(1),

                                        LanguageTabs::make([
                                            TextInput::make('value')
                                                ->label('Значення')
                                                ->required(),
                                        ])->columnSpan(1),

                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsible(),

                                LanguageTabs::make([
                                    RichEditor::make('additional_info')
                                        ->label('Додаткова інформація')
                                        ->columnSpanFull(),
                                ])->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Етапи')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Repeater::make('stages')
                                    ->label('Етапи реалізації')
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('order')
                                            ->label('№')
                                            ->numeric()
                                            ->default(0)
                                            ->columnSpan(1),

                                        Select::make('status')
                                            ->label('Статус')
                                            ->options(StageStatus::getOptions())
                                            ->default(StageStatus::Planned->value)
                                            ->required()
                                            ->columnSpan(2),

                                        LanguageTabs::make([
                                            TextInput::make('title')
                                                ->label('Назва етапу')
                                                ->required(),
                                            Textarea::make('description')
                                                ->label('Опис')
                                                ->rows(2),
                                        ])->columnSpanFull(),

                                        TextInput::make('days_planned')
                                            ->label('Днів')
                                            ->numeric()
                                            ->columnSpan(1),

                                        TextInput::make('budget_planned')
                                            ->label('Бюджет план')
                                            ->numeric()
                                            ->columnSpan(1),

                                        TextInput::make('budget_actual')
                                            ->label('Бюджет факт')
                                            ->numeric()
                                            ->columnSpan(1),

                                        DatePicker::make('started_at')
                                            ->label('Початок')
                                            ->columnSpan(1),

                                        DatePicker::make('completed_at')
                                            ->label('Завершено')
                                            ->columnSpan(1),

                                        Repeater::make('documents')
                                            ->label('Документи / Фото-звіти')
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Тип')
                                                    ->options([
                                                        'photo' => 'Фото',
                                                        'document' => 'Документ',
                                                    ])
                                                    ->default('photo')
                                                    ->required(),

                                                LanguageTabs::make([
                                                    TextInput::make('description')
                                                        ->label('Опис'),
                                                ])->columnSpan(1),

                                                FileUpload::make('file')
                                                    ->label('Файл')
                                                    ->disk('public')
                                                    ->directory('projects/stages/documents')
                                                    ->image()
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                                    ->maxSize(5120)
                                                    ->required()
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['description']['uk'] ?? ($state['type'] === 'photo' ? 'Фото' : 'Документ')),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title']['uk'] ?? 'Новий етап'),
                            ]),

                        Tabs\Tab::make('Бонуси')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                Repeater::make('bonuses')
                                    ->label('Бонуси для меценатів')
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('order')
                                            ->label('№')
                                            ->numeric()
                                            ->default(0)
                                            ->columnSpan(1),

                                        TextInput::make('min_donation')
                                            ->label('Мін. донат')
                                            ->numeric()
                                            ->required()
                                            ->columnSpan(1),

                                        TextInput::make('quantity')
                                            ->label('Кількість')
                                            ->numeric()
                                            ->placeholder('∞')
                                            ->helperText('Порожнє = необмежено')
                                            ->columnSpan(1),

                                        LanguageTabs::make([
                                            TextInput::make('title')
                                                ->label('Назва бонусу')
                                                ->required(),
                                            Textarea::make('description')
                                                ->label('Опис бонусу')
                                                ->rows(2),
                                        ])->columnSpanFull(),

                                        TextInput::make('quantity_claimed')
                                            ->label('Видано')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title']['uk'] ?? 'Новий бонус'),
                            ]),
                    ]),

                // Права колонка - статуси та дати
                Section::make('Статус')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Статус проєкту')
                            ->options(ProjectStatus::getOptions())
                            ->default(ProjectStatus::Draft->value)
                            ->required(),

                        Select::make('status_moderation')
                            ->label('Статус модерації')
                            ->options(ModerationStatus::getOptions())
                            ->default(ModerationStatus::Pending->value)
                            ->required(),

                        TextInput::make('code')
                            ->label('Код проєкту')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Генерується автоматично'),

                        TextInput::make('slug')
                            ->label('URL slug')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Генерується автоматично'),

                        Fieldset::make('Дати')
                            ->schema([
                                DatePicker::make('announced_at')
                                    ->label('Дата оголошення')->columnSpanFull(),

                                DatePicker::make('planned_completion_at')
                                    ->label('Планове завершення')->columnSpanFull(),

                                DatePicker::make('completed_at')
                                    ->label('Фактичне завершення')->columnSpanFull(),
                            ]),

                        Fieldset::make('Статистика')
                            ->schema([
                                TextInput::make('likes_count')
                                    ->label('Лайків')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),

                                TextInput::make('donors_count')
                                    ->label('Меценатів')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }
}
