<?php

namespace App\Filament\Profile\Resources\Projects\Schemas;

use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Jobs\GenerateStageDocumentPdfThumbnail;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Support\ProjectCategoryParameterValues;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Wizard::make([
                    Step::make(__('profile_projects.tabs.general'))
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
                                ->maxLength(255)
                                ->live(onBlur: true),

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

                    Step::make(__('profile_projects.tabs.budget'))
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
                                ->required()
                                ->live(onBlur: true),

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

                    Step::make(__('profile_projects.tabs.content'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Builder::make('content_blocks')
                                ->label(__('profile_projects.fields.content_blocks'))
                                ->addActionLabel(__('profile_projects.fields.content_block_add'))
                                ->blocks([
                                    Block::make('heading')
                                        ->label(fn (?array $state): string => filled($state['heading_text'] ?? null)
                                            ? $state['heading_text']
                                            : __('profile_projects.fields.content_block_type_heading'))
                                        ->icon('heroicon-o-bookmark')
                                        ->schema([
                                            TextInput::make('heading_text')
                                                ->label(__('profile_projects.fields.content_block_heading_text'))
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),

                                    Block::make('paragraph')
                                        ->label(fn (?array $state): string => filled($state['paragraph_text'] ?? null)
                                            ? Str::limit($state['paragraph_text'], 50)
                                            : __('profile_projects.fields.content_block_type_paragraph'))
                                        ->icon('heroicon-o-bars-3-bottom-left')
                                        ->schema([
                                            Textarea::make('paragraph_text')
                                                ->label(__('profile_projects.fields.content_block_paragraph_text'))
                                                ->required()
                                                ->rows(5)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),

                                    Block::make('image')
                                        ->label(__('profile_projects.fields.content_block_type_image'))
                                        ->icon('heroicon-o-photo')
                                        ->schema([
                                            FileUpload::make('image')
                                                ->label(__('profile_projects.fields.content_block_image'))
                                                ->image()
                                                ->imageEditor()
                                                ->disk('public')
                                                ->directory('projects/content-blocks')
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                ->maxSize(5120)
                                                ->imageCropAspectRatio('4:3')
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),
                                ])
                                ->columnSpanFull()
                                ->blockNumbers(false)
                                ->reorderable()
                                ->collapsed(),
                        ]),

                    Step::make(__('profile_projects.tabs.parameters'))
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
                                        ->table([
                                            TableColumn::make(__('profile_projects.fields.parameter_label')),
                                            TableColumn::make(__('profile_projects.fields.parameter_value')),
                                        ])
                                        ->schema([
                                            Hidden::make('parameter_id')
                                                ->required(),

                                            Hidden::make('parameter_type'),

                                            TextInput::make('parameter_label')
                                                ->hiddenLabel()
                                                ->disabled()
                                                ->dehydrated(false),

                                            Group::make([
                                                Select::make('parameter_value_id')
                                                    ->hiddenLabel()
                                                    ->options(
                                                        fn (callable $get) => Parameter::find($get('parameter_id'))
                                                            ?->values
                                                            ->mapWithKeys(fn (ParameterValue $value) => [$value->id => $value->getLabel('uk')])
                                                            ?? []
                                                    )
                                                    ->visible(fn (callable $get) => $get('parameter_type') === ParameterType::List->value),

                                                TextInput::make('custom_value')
                                                    ->hiddenLabel()
                                                    ->visible(fn (callable $get) => $get('parameter_type') === ParameterType::Custom->value),
                                            ]),
                                        ])
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make(__('profile_projects.tabs.stages'))
                        ->icon('heroicon-o-list-bullet')
                        ->schema([
                            Repeater::make('stages')
                                ->label(__('profile_projects.fields.stages'))
                                ->relationship()
                                ->orderColumn('order')
                                ->schema([
                                    ToggleButtons::make('status')
                                        ->label(__('profile_projects.fields.stage_status'))
                                        ->options(StageStatus::getOptions())
                                        ->colors(collect(StageStatus::cases())->mapWithKeys(fn (StageStatus $status) => [$status->value => $status->getColor()])->all())
                                        ->default(StageStatus::Planned->value)
                                        ->required()
                                        ->inline()
                                        ->grouped()
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                                            if ($state === StageStatus::InProgress->value && blank($get('started_at'))) {
                                                $set('started_at', now()->toDateString());
                                            }

                                            if ($state === StageStatus::Completed->value) {
                                                if (blank($get('started_at'))) {
                                                    $set('started_at', now()->toDateString());
                                                }

                                                if (blank($get('completed_at'))) {
                                                    $set('completed_at', now()->toDateString());
                                                }
                                            }
                                        }),

                                    TextInput::make('title')
                                        ->label(__('profile_projects.fields.stage_title'))
                                        ->required(),

                                    Textarea::make('description')
                                        ->label(__('profile_projects.fields.stage_description'))
                                        ->rows(2)
                                        ->autosize(),

                                    TextInput::make('budget_planned')
                                        ->label(__('profile_projects.fields.stage_budget_planned'))
                                        ->numeric()
                                        ->default(0)
                                        ->required(fn (callable $get): bool => $get('status') === StageStatus::Planned->value),

                                    TextInput::make('budget_actual')
                                        ->label(__('profile_projects.fields.stage_budget_actual'))
                                        ->numeric()
                                        ->visible(fn (callable $get): bool => $get('status') === StageStatus::Completed->value)
                                        ->required(fn (callable $get): bool => $get('status') === StageStatus::Completed->value),

                                    FileUpload::make('documents')
                                        ->label(__('profile_projects.fields.stage_documents'))
                                        ->visible(fn (callable $get): bool => $get('status') === StageStatus::Completed->value)
                                        ->required(fn (callable $get): bool => $get('status') === StageStatus::Completed->value)
                                        ->multiple()
                                        ->panelLayout('grid')
//                                        ->imagePreviewHeight('200')
                                        ->downloadable()
                                        ->openable()
                                        ->disk('public')
                                        ->directory('projects/stage-documents')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                        ->maxSize(5120)
                                        // За замовчуванням FileUpload лише прибирає файл зі стану форми,
                                        // фізично на диску він лишається — видаляємо його самі (і мініатюру
                                        // для PDF, якщо вона встигла згенеруватись).
                                        ->deleteUploadedFileUsing(function (mixed $file): void {
                                            // $file буває TemporaryUploadedFile, якщо видаляють щойно
                                            // завантажений (ще не збережений) файл — його прибере сам
                                            // Livewire, чіпати диск не треба.
                                            if (! is_string($file)) {
                                                return;
                                            }

                                            Storage::disk('public')->delete($file);

                                            $thumbnailPath = GenerateStageDocumentPdfThumbnail::thumbnailPathFor($file);

                                            if (Storage::disk('public')->exists($thumbnailPath)) {
                                                Storage::disk('public')->delete($thumbnailPath);
                                            }
                                        })
                                        // У БД зберігаємо збагачений формат [{type,file,file_url,uploaded_at}]
                                        // (сумісний з API), а FileUpload::multiple() працює лише з плоским
                                        // масивом шляхів — конвертуємо в обидва боки на межі поля.
                                        ->afterStateHydrated(function (FileUpload $component, mixed $state): void {
                                            if (! is_array($state)) {
                                                return;
                                            }

                                            $component->state(
                                                collect($state)
                                                    ->map(fn (mixed $item): mixed => is_array($item) ? ($item['file'] ?? null) : $item)
                                                    ->filter()
                                                    ->values()
                                                    ->all()
                                            );
                                        })
                                        ->dehydrateStateUsing(fn (?array $state): array => collect($state ?? [])
                                            ->map(function (string $path): array {
                                                $isPdf = str_ends_with(strtolower($path), '.pdf');

                                                return [
                                                    'type' => $isPdf ? 'document' : 'photo',
                                                    'file' => $path,
                                                    'file_url' => Storage::disk('public')->url($path),
                                                    'uploaded_at' => now()->toISOString(),
                                                ];
                                            })
                                            ->values()
                                            ->all()),

                                    Hidden::make('started_at'),
                                    Hidden::make('completed_at'),
                                ])
                                ->columns(1)
                                ->columnSpanFull()
                                ->defaultItems(0)
                                ->reorderable()
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? __('profile_projects.defaults.stage_title')),
                        ]),

                    Step::make(__('profile_projects.tabs.bonuses'))
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
                ])
                    ->columnSpan(2)
                    ->persistStepInQueryString()
                    // При редагуванні існуючого проєкту дозволяємо вільно перемикатись
                    // між кроками клацанням, як у табах — усі дані вже заповнені, тому
                    // послідовна валідація "Далі/Назад" тут тільки заважає. При створенні
                    // нового проєкту крокова навігація лишається (record ще не існує).
                    ->skippable(fn (?Project $record): bool => $record !== null),

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
