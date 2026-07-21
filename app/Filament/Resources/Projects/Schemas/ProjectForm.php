<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Enums\UserType;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Support\ProjectCategoryParameterValues;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
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
                                Section::make('Автор та назва')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Автор')
                                            ->relationship(
                                                'user',
                                                modifyQueryUsing: fn ($query) => $query->orderBy('email')
                                            )
                                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name ?: $record->email)
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('user_type')
                                            ->label('Тип власника')
                                            ->options(UserType::getOptions())
                                            ->default(UserType::Personal->value)
                                            ->required(),
                                    ])
                                    ->columns(2),

                                Section::make('Категорія')
                                    ->schema([
                                        Select::make('art_category_id')
                                            ->label('Галузь мистецтва')
                                            ->options(function () {
                                                $options = [];
                                                foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
                                                    $options[$root->getLabel('uk')] = [
                                                        (string) $root->id => $root->getLabel('uk'),
                                                    ];
                                                    foreach ($root->children as $child) {
                                                        $options[$root->getLabel('uk')][(string) $child->id] = '  '.$child->getLabel('uk');
                                                    }
                                                }

                                                return $options;
                                            })
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function (mixed $state, callable $set, CreateRecord|EditRecord $livewire): void {
                                                $project = $livewire instanceof EditRecord && $livewire->getRecord() instanceof Project
                                                    ? $livewire->getRecord()
                                                    : null;

                                                $set(
                                                    'project_parameter_values',
                                                    ProjectCategoryParameterValues::rowsForCategoryId(
                                                        filled($state) ? (int) $state : null,
                                                        $project,
                                                    ),
                                                );
                                            })
                                            ->required(),
                                    ]),

                                LanguageTabs::make([

                                    TextInput::make('title')
                                        ->label('Назва проєкту')
                                        ->required()
                                        ->maxLength(255),

                                    Textarea::make('short_description')
                                        ->label('Короткий опис')
                                        ->rows(3),

                                    TextInput::make('tags')
                                        ->label('Теги (через кому)'),

                                ])->columnSpanFull(),

                                FileUpload::make('cover')
                                    ->label('Обкладинка')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('projects/covers')
                                    ->columnSpanFull(),
                            ]),

                        //                        Tabs\Tab::make('Категорія')
                        //                            ->icon('heroicon-o-tag')
                        //                            ->schema([
                        //
                        //                                ])->columnSpanFull(),
                        //                            ]),

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
                                    ->label('Деталі бюджету')
                                    ->schema([
                                        LanguageTabs::make([
                                            TextInput::make('name')
                                                ->label('Назва')
                                                ->required(),
                                        ])->columnSpan(2),
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
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => ($state['name']['uk'] ?? 'Без назви').
                                        (isset($state['amount']) ? ' — '.number_format($state['amount'], 2).' ₴' : '')
                                    ),
                            ]),

                        //                        Tabs\Tab::make('Додаткова інформація')
                        //                            ->icon('heroicon-o-ellipsis-horizontal')
                        //                            ->schema([
                        //                                LanguageTabs::make([
                        //                                    Textarea::make('additional_info')
                        //                                        ->label('Додаткова інформація')
                        //                                        ->rows(5)
                        //                                        ->columnSpanFull(),
                        //                                ])->columnSpanFull(),
                        //                            ]),

                        Tabs\Tab::make('Характеристики')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Характеристики')
                                    ->description('Автоматично показуються всі характеристики обраної категорії мистецтва. Рядок зберігається лише коли заповнено значення; інакше запис видаляється.')
                                    ->headerActions([
                                        self::createParameterForProjectCategoryAction(),
                                    ])
                                    ->schema([
                                        Placeholder::make('category_required_for_parameters')
                                            ->label('')
                                            ->content('Спочатку оберіть галузь мистецтва на вкладці «Загальне», щоб з’явився список характеристик.')
                                            ->visible(fn (callable $get): bool => ! filled($get('art_category_id'))),

                                        Repeater::make('project_parameter_values')
                                            ->label('')
                                            ->visible(fn (callable $get): bool => filled($get('art_category_id')))
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->defaultItems(0)
                                            ->schema(self::categoryParameterValueRowSchema())
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Контент')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Repeater::make('content_blocks')
                                    ->label('Контент-блоки')
                                    ->schema([
                                        Select::make('type')
                                            ->label('Тип блоку')
                                            ->options([
                                                'heading' => 'Заголовок',
                                                'paragraph' => 'Параграф',
                                                'image' => 'Зображення',
                                            ])
                                            ->default('paragraph')
                                            ->required()
                                            ->reactive()
                                            ->columnSpanFull(),

                                        // ПОЛЯ ДЛЯ ЗАГОЛОВКА
                                        Select::make('heading_level')
                                            ->label('Рівень заголовка')
                                            ->options([
                                                'h2' => 'H2 - Великий заголовок',
                                                'h3' => 'H3 - Середній заголовок',
                                                'h4' => 'H4 - Малий заголовок',
                                                'h5' => 'H5 - Дрібний заголовок',
                                                'h6' => 'H6 - Мінімальний заголовок',
                                            ])
                                            ->default('h2')
                                            ->required()
                                            ->visible(fn (callable $get) => $get('type') === 'heading')
                                            ->columnSpanFull(),

                                        LanguageTabs::make([
                                            TextInput::make('heading_text')
                                                ->label('Текст заголовка')
                                                ->required()
                                                ->maxLength(255),
                                        ])
                                            ->visible(fn (callable $get) => $get('type') === 'heading')
                                            ->columnSpanFull(),

                                        // ПОЛЯ ДЛЯ ПАРАГРАФА
                                        LanguageTabs::make([
                                            Textarea::make('paragraph_text')
                                                ->label('Текст параграфа')
                                                ->required()
                                                ->rows(5),
                                        ])
                                            ->visible(fn (callable $get) => $get('type') === 'paragraph')
                                            ->columnSpanFull(),

                                        // ПОЛЯ ДЛЯ ЗОБРАЖЕННЯ
                                        FileUpload::make('image')
                                            ->label('Зображення')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('projects/content-blocks')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(5120)
                                            ->required()
                                            ->visible(fn (callable $get) => $get('type') === 'image')
                                            ->columnSpanFull(),

                                        //                                        LanguageTabs::make([
                                        //                                            TextInput::make('image_alt')
                                        //                                                ->label('Alt текст (опис зображення)')
                                        //                                                ->helperText('Важливо для доступності та SEO'),
                                        //                                        ])
                                        //                                            ->visible(fn (callable $get) => $get('type') === 'image')
                                        //                                            ->columnSpanFull(),
                                        //
                                        //                                        LanguageTabs::make([
                                        //                                            TextInput::make('image_caption')
                                        //                                                ->label('Підпис під зображенням')
                                        //                                                ->helperText('Необов\'язково'),
                                        //                                        ])
                                        //                                            ->visible(fn (callable $get) => $get('type') === 'image')
                                        //                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => match ($state['type'] ?? null) {
                                        'heading' => strtoupper($state['heading_level'] ?? 'h2').': '.($state['heading_text']['uk'] ?? 'Новий заголовок'),
                                        'paragraph' => 'Параграф: '.\Illuminate\Support\Str::limit($state['paragraph_text']['uk'] ?? 'Новий параграф', 50),
                                        'image' => 'Зображення'.(isset($state['image_alt']['uk']) ? ': '.$state['image_alt']['uk'] : ''),
                                        default => 'Новий блок',
                                    }),
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
                                            ->collapsed()
                                            ->itemLabel(fn (array $state): ?string => $state['description']['uk'] ?? ($state['type'] === 'photo' ? 'Фото' : 'Документ')),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsed()
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

                                        TextInput::make('max_donation')
                                            ->label('Макс. донат')
                                            ->numeric()
                                            ->gt('min_donation')
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
                                    ->columns(4)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title']['uk'] ?? 'Новий бонус'),
                            ]),
                    ]), // Закриваємо tabs()

                // Права колонка - статуси та дати
                Section::make('Статус')
                    ->columnSpan(1)
                    ->schema([

                        // is_legal - відображається тільки для адмінів, тому що це поле проєкту, а не користувача
                        //                        Toggle::make('is_legal')
                        //                            ->label('Юридична особа')
                        //                            ->helperText('Проєкт буде відображатися з юридичним статусом, незалежно від статусу користувача-автора')
                        //                            ->default(false),

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

    private static function createParameterForProjectCategoryAction(): Action
    {
        return Action::make('createParameterForProjectCategory')
            ->label('Додати параметр')
            ->icon('heroicon-o-plus-circle')
            ->modalHeading('Нова характеристика категорії')
            ->modalDescription('Характеристика з’явиться в цьому списку для обраної галузі мистецтва. Для типу «Список значень» самі значення додаються окремо в розділі «Характеристики» після створення.')
            ->modalSubmitActionLabel('Створити')
            ->successNotificationTitle('Характеристику додано')
            ->visible(fn (callable $get): bool => filled($get('art_category_id')))
            ->schema([
                Select::make('type')
                    ->label('Тип')
                    ->options(ParameterType::getOptions())
                    ->default(ParameterType::List->value)
                    ->required(),

                LanguageTabs::make([
                    TextInput::make('name')
                        ->label('Назва')
                        ->required()
                        ->maxLength(255),
                ])->columnSpanFull(),

                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ])
            ->action(function (array $data, CreateRecord|EditRecord $livewire): void {
                $categoryId = (int) ($livewire->data['art_category_id'] ?? 0);

                if (! $categoryId) {
                    return;
                }

                Parameter::query()->create([
                    'art_category_id' => $categoryId,
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'sort_order' => (int) $data['sort_order'],
                ]);

                $project = $livewire instanceof EditRecord && $livewire->getRecord() instanceof Project
                    ? $livewire->getRecord()
                    : null;

                $livewire->data['project_parameter_values'] = ProjectCategoryParameterValues::rowsForCategoryId($categoryId, $project);
            });
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    private static function categoryParameterValueRowSchema(): array
    {
        return [
            Hidden::make('parameter_id')
                ->required(),

            Hidden::make('parameter_type'),

            TextInput::make('parameter_label')
                ->label('Характеристика')
                ->disabled()
                ->dehydrated(false)
                ->columnSpan(1),

            Select::make('parameter_value_id')
                ->label('Значення')
                ->options(
                    fn (callable $get) => Parameter::find($get('parameter_id'))
                        ?->values
                        ->mapWithKeys(fn (ParameterValue $value) => [$value->id => $value->getLabel('uk')])
                        ?? []
                )
                ->visible(fn (callable $get) => $get('parameter_type') === ParameterType::List->value)
                ->columnSpan(2),

            LanguageTabs::make([
                TextInput::make('custom_value')
                    ->label('Значення'),
            ])
                ->visible(fn (callable $get) => $get('parameter_type') === ParameterType::Custom->value)
                ->columnSpan(2),
        ];
    }
}
