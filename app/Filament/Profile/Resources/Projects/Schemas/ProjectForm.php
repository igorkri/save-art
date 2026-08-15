<?php

namespace App\Filament\Profile\Resources\Projects\Schemas;

use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
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
use Filament\Forms\Components\TagsInput;
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
                Tabs::make(__('profile_projects.tabs.main'))
                    ->columnSpan(2)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make(__('profile_projects.tabs.general'))
                            ->key('general')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Select::make('art_category_id')
                                    ->label(__('profile_projects.fields.art_category'))
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
                                    ->label(__('profile_projects.fields.title'))
                                    ->required()
                                    ->maxLength(255),

                                Textarea::make('short_description')
                                    ->label(__('profile_projects.fields.short_description'))
                                    ->autosize()
                                    ->maxLength(500)
                                    ->rows(3),

                                TagsInput::make('tags')
                                    ->label(__('profile_projects.fields.tags')),

                                FileUpload::make('cover')
                                    ->label(__('profile_projects.fields.cover'))
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatioOptions([
                                        null,
                                        '4:3',
                                        //                                        '1:1',
                                    ])
                                    ->disk('public')
                                    ->directory('projects/covers')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make(__('profile_projects.tabs.budget'))
                            ->key('budget')
                            ->icon('heroicon-o-currency-dollar')
                            ->columns(2)
                            ->schema([
                                Select::make('currency')
                                    ->label(__('profile_projects.fields.currency'))
                                    ->options([
                                        Currency::UAH->value => '₴ UAH',
                                        Currency::USD->value => '$ USD',
                                        Currency::EUR->value => '€ EUR',
                                    ])
                                    ->default(Currency::UAH->value)
                                    ->required(),

                                TextInput::make('budget_goal')
                                    ->label(__('profile_projects.fields.budget_goal'))
                                    ->numeric()
                                    ->prefix(fn (callable $get) => match ($get('currency')) {
                                        'USD' => '$',
                                        'EUR' => '€',
                                        default => '₴',
                                    })
                                    ->required(),

                                TextInput::make('estimated_days')
                                    ->label(__('profile_projects.fields.estimated_days'))
                                    ->numeric()
                                    ->minValue(1),

                                Repeater::make('budget_items')
                                    ->label(__('profile_projects.fields.budget_items'))
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('profile_projects.fields.budget_item_name'))
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('amount')
                                            ->label(__('profile_projects.fields.budget_item_amount'))
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
                                    ->itemLabel(fn (array $state): ?string => ($state['name'] ?? __('profile_projects.defaults.budget_item_name')).
                                        (isset($state['amount']) ? ' — '.number_format($state['amount'], 2).' ₴' : '')
                                    ),
                            ]),

                        Tabs\Tab::make(__('profile_projects.tabs.parameters'))
                            ->key('parameters')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make(__('profile_projects.sections.parameters.title'))
                                    ->description(__('profile_projects.sections.parameters.description'))
                                    ->schema([
                                        Placeholder::make('category_required_for_parameters')
                                            ->label('')
                                            ->content(__('profile_projects.fields.parameter_placeholder'))
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
                                                    ->label(__('profile_projects.fields.parameter_label'))
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(1),

                                                Select::make('parameter_value_id')
                                                    ->label(__('profile_projects.fields.parameter_value'))
                                                    ->options(
                                                        fn (callable $get) => Parameter::find($get('parameter_id'))
                                                            ?->values
                                                            ->mapWithKeys(fn (ParameterValue $value) => [$value->id => $value->getLabel('uk')])
                                                            ?? []
                                                    )
                                                    ->visible(fn (callable $get) => $get('parameter_type') === ParameterType::List->value)
                                                    ->columnSpan(2),

                                                TextInput::make('custom_value')
                                                    ->label(__('profile_projects.fields.parameter_value'))
                                                    ->visible(fn (callable $get) => $get('parameter_type') === ParameterType::Custom->value)
                                                    ->columnSpan(2),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make(__('profile_projects.tabs.stages'))
                            ->key('stages')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Repeater::make('stages')
                                    ->label(__('profile_projects.fields.stages'))
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('order')
                                            ->label(__('profile_projects.fields.stage_order'))
                                            ->numeric()
                                            ->default(0)
                                            ->columnSpan(1),

                                        Select::make('status')
                                            ->label(__('profile_projects.fields.stage_status'))
                                            ->options(StageStatus::getOptions())
                                            ->default(StageStatus::Planned->value)
                                            ->required()
                                            ->columnSpan(2),

                                        TextInput::make('title')
                                            ->label(__('profile_projects.fields.stage_title'))
                                            ->required()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label(__('profile_projects.fields.stage_description'))
                                            ->rows(2)
                                            ->autosize()
                                            ->columnSpanFull(),

                                        TextInput::make('days_planned')
                                            ->label(__('profile_projects.fields.stage_days_planned'))
                                            ->numeric()
                                            ->columnSpan(1),

                                        DatePicker::make('started_at')
                                            ->label(__('profile_projects.fields.stage_started_at'))
                                            ->columnSpan(1),

                                        DatePicker::make('completed_at')
                                            ->label(__('profile_projects.fields.stage_completed_at'))
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? __('profile_projects.defaults.stage_title')),
                            ]),

                        Tabs\Tab::make(__('profile_projects.tabs.bonuses'))
                            ->key('bonuses')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                Repeater::make('bonuses')
                                    ->label(__('profile_projects.fields.bonuses'))
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('order')
                                            ->label(__('profile_projects.fields.bonus_order'))
                                            ->numeric()
                                            ->default(0)
                                            ->columnSpan(1),

                                        TextInput::make('min_donation')
                                            ->label(__('profile_projects.fields.bonus_min_donation'))
                                            ->numeric()
                                            ->required()
                                            ->columnSpan(1),

                                        TextInput::make('max_donation')
                                            ->label(__('profile_projects.fields.bonus_max_donation'))
                                            ->numeric()
                                            ->gt('min_donation')
                                            ->columnSpan(1),

                                        TextInput::make('quantity')
                                            ->label(__('profile_projects.fields.bonus_quantity'))
                                            ->numeric()
                                            ->placeholder(__('profile_projects.placeholders.bonus_quantity'))
                                            ->helperText(__('profile_projects.helpers.bonus_quantity'))
                                            ->columnSpan(1),

                                        TextInput::make('title')
                                            ->label(__('profile_projects.fields.bonus_title'))
                                            ->required()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label(__('profile_projects.fields.bonus_description'))
                                            ->rows(2)
                                            ->autosize()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? __('profile_projects.defaults.bonus_title')),
                            ]),
                    ]),

                Section::make(__('profile_projects.sections.status'))
                    ->icon('heroicon-o-flag')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('status_display')
                            ->label(__('profile_projects.fields.status_display'))
                            ->content(fn (?Project $record) => $record?->status)
                            ->formatStateUsing(fn (?ProjectStatus $state) => $state?->getLabel() ?? __('profile_projects.defaults.status_display'))
                            ->badge()
                            ->color(fn (?ProjectStatus $state): string => $state?->getColor() ?? 'gray'),

                        Placeholder::make('status_moderation_display')
                            ->label(__('profile_projects.fields.moderation_display'))
                            ->content(fn (?Project $record) => $record?->status_moderation)
                            ->formatStateUsing(fn (?ModerationStatus $state) => $state?->getLabel() ?? __('profile_projects.defaults.empty'))
                            ->badge()
                            ->color(fn (?ModerationStatus $state): string => $state?->getColor() ?? 'gray')
                            ->visible(fn (?Project $record): bool => filled($record?->status_moderation)),

                        Placeholder::make('code_display')
                            ->label(__('profile_projects.fields.code_display'))
                            ->content(fn (?Project $record) => $record?->code ?? __('profile_projects.defaults.code_display'))
                            ->copyable(fn (?Project $record): bool => filled($record?->code))
                            ->fontFamily('mono'),

                        Fieldset::make(__('profile_projects.sections.dates'))
                            ->schema([
                                DatePicker::make('announced_at')
                                    ->label(__('profile_projects.fields.announced_at'))->columnSpanFull(),

                                DatePicker::make('planned_completion_at')
                                    ->label(__('profile_projects.fields.planned_completion_at'))->columnSpanFull(),
                            ]),

                        Fieldset::make(__('profile_projects.sections.stats'))
                            ->schema([
                                Placeholder::make('likes_count_display')
                                    ->label(__('profile_projects.fields.likes_count'))
                                    ->content(fn (?Project $record) => (string) ($record?->likes_count ?? 0)),

                                Placeholder::make('donors_count_display')
                                    ->label(__('profile_projects.fields.donors_count'))
                                    ->content(fn (?Project $record) => (string) ($record?->donors_count ?? 0)),

                                Placeholder::make('budget_collected_display')
                                    ->label(__('profile_projects.fields.budget_collected'))
                                    ->content(fn (?Project $record) => number_format((float) ($record?->budget_collected ?? 0), 2).' '.($record?->currency?->value ?? '')),
                            ]),
                    ]),
            ]);
    }
}
