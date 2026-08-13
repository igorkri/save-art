<?php

namespace App\Filament\Profile\Resources\Projects\Schemas;

use App\Enums\Currency;
use App\Enums\ParameterType;
use App\Enums\StageStatus;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Support\ProjectCategoryParameterValues;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Tabs::make('Основна інформація')
                    ->columnSpan(2)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make('Загальне')
                            ->icon('heroicon-o-information-circle')
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

                                TextInput::make('title')
                                    ->label('Назва проєкту')
                                    ->required()
                                    ->maxLength(255),

                                Textarea::make('short_description')
                                    ->label('Короткий опис')
                                    ->rows(3),

                                TextInput::make('tags')
                                    ->label('Теги (через кому)'),

                                FileUpload::make('cover')
                                    ->label('Обкладинка')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('projects/covers')
                                    ->columnSpanFull(),
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

                                TextInput::make('estimated_days')
                                    ->label('Орієнтовна кількість днів')
                                    ->numeric()
                                    ->minValue(1),

                                Repeater::make('budget_items')
                                    ->label('Деталі бюджету')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Назва')
                                            ->required()
                                            ->columnSpan(2),
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
                                    ->itemLabel(fn (array $state): ?string => ($state['name'] ?? 'Без назви').
                                        (isset($state['amount']) ? ' — '.number_format($state['amount'], 2).' ₴' : '')
                                    ),
                            ]),

                        Tabs\Tab::make('Характеристики')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Характеристики')
                                    ->description('Автоматично показуються всі характеристики обраної галузі мистецтва.')
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
                                            ->schema([
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

                                                TextInput::make('custom_value')
                                                    ->label('Значення')
                                                    ->visible(fn (callable $get) => $get('parameter_type') === ParameterType::Custom->value)
                                                    ->columnSpan(2),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ]),
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

                                        TextInput::make('title')
                                            ->label('Назва етапу')
                                            ->required()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Опис')
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        TextInput::make('days_planned')
                                            ->label('Днів')
                                            ->numeric()
                                            ->columnSpan(1),

                                        DatePicker::make('started_at')
                                            ->label('Початок')
                                            ->columnSpan(1),

                                        DatePicker::make('completed_at')
                                            ->label('Завершено')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Новий етап'),
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

                                        TextInput::make('title')
                                            ->label('Назва бонусу')
                                            ->required()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Опис бонусу')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Новий бонус'),
                            ]),
                    ]),

                Section::make('Статус')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('status_display')
                            ->label('Статус проєкту')
                            ->content(fn (?Project $record) => $record?->status?->getLabel() ?? 'Чернетка (ще не збережено)'),

                        Placeholder::make('status_moderation_display')
                            ->label('Статус модерації')
                            ->content(fn (?Project $record) => $record?->status_moderation?->getLabel() ?? '—'),

                        Placeholder::make('code_display')
                            ->label('Код проєкту')
                            ->content(fn (?Project $record) => $record?->code ?? 'Генерується автоматично'),

                        Fieldset::make('Дати')
                            ->schema([
                                DatePicker::make('announced_at')
                                    ->label('Дата оголошення')->columnSpanFull(),

                                DatePicker::make('planned_completion_at')
                                    ->label('Планове завершення')->columnSpanFull(),
                            ]),

                        Fieldset::make('Статистика')
                            ->schema([
                                Placeholder::make('likes_count_display')
                                    ->label('Лайків')
                                    ->content(fn (?Project $record) => (string) ($record?->likes_count ?? 0)),

                                Placeholder::make('donors_count_display')
                                    ->label('Меценатів')
                                    ->content(fn (?Project $record) => (string) ($record?->donors_count ?? 0)),

                                Placeholder::make('budget_collected_display')
                                    ->label('Зібрано')
                                    ->content(fn (?Project $record) => number_format((float) ($record?->budget_collected ?? 0), 2).' '.($record?->currency?->value ?? '')),
                            ]),
                    ]),
            ]);
    }
}
