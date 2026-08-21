<?php

namespace App\Filament\Profile\Resources\Projects\Schemas;

use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Enums\UserType;
use App\Jobs\GenerateStageDocumentPdfThumbnail;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Team;
use App\Support\ProjectCategoryParameterValues;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Webkul\ProgressStepper\Forms\Components\ProgressStepper;

use function Filament\Support\generate_icon_html;

class ProjectForm
{
    private static function cannotFullyEdit(?Project $record): bool
    {
        return $record?->exists === true && ! $record->canBeFullyEditedByOwner();
    }

    private static function cannotEditPublishedFields(?Project $record): bool
    {
        return $record?->exists === true
            && ! $record->canBeFullyEditedByOwner()
            && ! $record->isPartiallyEditable();
    }

    private static function cannotEditAdditionalContent(?Project $record): bool
    {
        return $record?->exists === true
            && ! $record->canBeFullyEditedByOwner()
            && ! $record->isPartiallyEditable()
            && ! $record->canEditAdditionalContentOnly();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->components([
                ProgressStepper::make('status')
                    ->hiddenLabel()
                    // Крок "Відхилений" — не звичайний етап флоу (проєкт або йде New →
                    // ... → Sold, або "звалюється" у Rejected). Показуємо його в стрічці
                    // лише коли проєкт справді відхилений, інакше він лише плутав би
                    // користувача, торчачи серед майбутніх кроків.
                    ->options(fn (?Project $record): array => $record?->status === ProjectStatus::Rejected
                        ? ProjectStatus::getOptions()
                        : collect(ProjectStatus::getOptions())->except(ProjectStatus::Rejected->value)->all())
                    ->icons(collect(ProjectStatus::cases())->mapWithKeys(fn (ProjectStatus $status) => [$status->value => $status->getIcon()])->all())
                    ->markCompletedUpToCurrent()
                    ->completedColor('success')
                    ->currentColor('primary')
                    ->upcomingColor('gray')
                    ->errorColor('danger')
                    ->errorStates(fn (?Project $record): array => $record?->status === ProjectStatus::Rejected
                        ? [ProjectStatus::Rejected->value]
                        : [])
                    ->default(ProjectStatus::New->value)
                    ->disabled()
                    ->dehydrated(false)
                    ->extraAttributes(['class' => 'profile-project-status-stepper'])
                    ->columnSpanFull(),

                Group::make()
                    ->extraAttributes(['class' => 'profile-project-summary'])
                    ->columnSpanFull()
                    ->columns([
                        'default' => 2,
                        'sm' => 3,
                        'md' => 4,
                        'xl' => 8,
                    ])
                    ->visible(fn (?Project $record): bool => $record !== null)
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

                        Placeholder::make('announced_at_display')
                            ->label(__('profile_projects.fields.announced_at'))
                            ->content(fn (?Project $record) => $record?->announced_at?->translatedFormat('d.m.Y') ?? '—'),

                        Placeholder::make('planned_completion_at_display')
                            ->label(__('profile_projects.fields.planned_completion_at'))
                            ->content(fn (?Project $record) => $record?->planned_completion_at?->translatedFormat('d.m.Y') ?? '—'),

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

                Wizard::make(self::orderedWizardSteps([
                    Step::make(__('profile_projects.tabs.author'))
                        ->icon('heroicon-o-user-circle')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-author'])
                        ->disabled(fn (?Project $record): bool => self::cannotFullyEdit($record))
                        ->schema([
                            Hidden::make('team_id'),
                            Hidden::make('user_type')->default(UserType::Personal->value),
                            Hidden::make('is_legal')->default(false),

                            Radio::make('project_owner')
                                ->label(__('profile_projects.fields.project_owner'))
                                ->helperText(__('profile_projects.helpers.project_owner'))
                                ->options(fn (): array => self::projectOwnerOptions())
                                ->default('personal')
                                ->afterStateHydrated(fn (Radio $component, ?Project $record) => $component->state(
                                    $record?->team_id
                                        ? 'team:'.$record->team_id
                                        : ($record?->is_legal ? 'legal' : 'personal')
                                ))
                                ->live()
                                ->afterStateUpdated(function (mixed $state, callable $set): void {
                                    $isTeam = str_starts_with((string) $state, 'team:');
                                    $isLegal = $state === 'legal';

                                    $set('team_id', $isTeam ? (int) Str::after((string) $state, 'team:') : null);
                                    $set('user_type', $isTeam ? UserType::Team->value : ($isLegal ? UserType::Legal->value : UserType::Personal->value));
                                    $set('is_legal', $isLegal);
                                })
                                ->dehydrated(false)
                                ->required()
                                ->columns(1)
                                ->extraAttributes(fn (): array => [
                                    'class' => 'profile-project-owner-radio',
                                    'style' => self::projectOwnerAvatarStyles(),
                                ])
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-owner-field']),
                        ]),

                    Step::make(__('profile_projects.tabs.name'))
                        ->icon('heroicon-o-pencil-square')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-name'])
                        ->schema([
                            TextInput::make('title')
                                ->label(__('profile_projects.fields.title'))
                                ->helperText(__('profile_projects.helpers.title'))
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn (?Project $record): bool => self::cannotFullyEdit($record))
                                ->live(onBlur: true),

                            SelectTree::make('art_category_id')
                                ->label(__('profile_projects.fields.art_category'))
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-art-category-field'])
                                ->relationship('artCategory', 'name', 'parent_id')
                                ->disabled(fn (?Project $record): bool => self::cannotEditPublishedFields($record))
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

                            FileUpload::make('cover')
                                ->label(__('profile_projects.fields.cover'))
                                ->helperText(__('profile_projects.helpers.cover'))
                                ->image()
                                ->imageEditor()
                                ->imageEditorAspectRatioOptions([null, '4:3'])
                                ->panelAspectRatio('4:3')
                                ->panelLayout('integrated')
                                ->disk('public')
                                ->directory('projects/covers')
                                ->disabled(fn (?Project $record): bool => self::cannotFullyEdit($record))
                                ->extraFieldWrapperAttributes(['class' => 'profile-primary-image-field'])
                                ->columnSpanFull(),

                            TagsInput::make('tags')
                                ->label(__('profile_projects.fields.tags'))
                                ->disabled(fn (?Project $record): bool => self::cannotFullyEdit($record))
                                ->helperText(__('profile_projects.helpers.tags')),
                        ]),

                    Step::make(__('profile_projects.tabs.description'))
                        ->icon('heroicon-o-document-text')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-description'])
                        ->disabled(fn (?Project $record): bool => self::cannotFullyEdit($record))
                        ->schema([
                            Textarea::make('short_description')
                                ->label(__('profile_projects.fields.short_description'))
                                ->placeholder(__('profile_projects.placeholders.short_description'))
                                ->helperText(__('profile_projects.helpers.short_description'))
                                ->maxLength(500)
                                ->rows(9)
                                ->columnSpanFull(),
                        ]),

                    Step::make(__('profile_projects.tabs.budget'))
                        ->icon('heroicon-o-currency-dollar')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-budget'])
                        ->columns(1)
                        ->disabled(fn (?Project $record): bool => self::cannotEditPublishedFields($record))
                        ->schema([
                            Select::make('currency')
                                ->hiddenLabel()
                                ->options([
                                    Currency::UAH->value => '₴ UAH',
                                    Currency::USD->value => '$ USD',
                                    Currency::EUR->value => '€ EUR',
                                ])
                                ->default(Currency::UAH->value)
                                ->disabled(fn (?Project $record): bool => self::cannotFullyEdit($record))
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-budget-currency'])
                                ->required(),

                            TextInput::make('budget_goal')
                                ->label(fn (callable $get): string => __('profile_projects.fields.budget_goal_with_currency', [
                                    'currency' => $get('currency') ?: Currency::UAH->value,
                                ]))
                                ->numeric()
                                ->placeholder('0')
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-budget-goal'])
                                ->required()
                                ->live(onBlur: true),

                            Placeholder::make('budget_platform_fee')
                                ->hiddenLabel()
                                ->content(__('profile_projects.budget.platform_fee'))
                                ->extraAttributes(['class' => 'profile-project-budget-fee']),

                            TextInput::make('estimated_days')
                                ->label(__('profile_projects.fields.estimated_days_description'))
                                ->numeric()
                                ->placeholder('0')
                                ->minValue(1)
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-budget-days'])
                                ->disabled(fn (?Project $record): bool => self::cannotFullyEdit($record)),

                            Placeholder::make('budget_details_intro')
                                ->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div class="profile-project-budget-intro">'
                                    .'<p>'.e(__('profile_projects.budget.details_title')).'</p>'
                                    .'<p>'.e(__('profile_projects.budget.details_description')).'</p>'
                                    .'<p>'.e(__('profile_projects.budget.details_notice')).'</p>'
                                    .'</div>'
                                ))
                                ->extraAttributes(['class' => 'profile-project-budget-intro-field']),

                            Repeater::make('budget_items')
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make(__('profile_projects.fields.budget_item_name'))
                                        ->markAsRequired(),
                                    TableColumn::make(__('profile_projects.fields.budget_item_amount'))
                                        ->width('136px')
                                        ->markAsRequired(),
                                ])
                                ->schema([
                                    TextInput::make('name')
                                        ->hiddenLabel()
                                        ->placeholder(__('profile_projects.placeholders.budget_item_name'))
                                        ->extraFieldWrapperAttributes(['class' => 'profile-project-budget-item-name'])
                                        ->required(),
                                    TextInput::make('amount')
                                        ->hiddenLabel()
                                        ->numeric()
                                        ->placeholder('0')
                                        ->extraFieldWrapperAttributes(['class' => 'profile-project-budget-item-amount'])
                                        ->required(),
                                ])
                                ->columnSpanFull()
                                ->defaultItems(0)
                                ->reorderable(false)
                                ->collapsible(false)
                                ->addAction(fn (Action $action): Action => $action
                                    ->label(__('profile_projects.actions.add_budget_item'))
                                    ->icon('heroicon-m-plus')
                                    ->iconButton())
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-budget-repeater']),
                        ]),

                    Step::make(__('profile_projects.tabs.content'))
                        ->icon('heroicon-o-document-text')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-content'])
                        ->disabled(fn (?Project $record): bool => self::cannotEditAdditionalContent($record))
                        ->schema([
                            Placeholder::make('content_blocks_intro')
                                ->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div class="profile-project-content-intro">'
                                    .'<p>'.e(__('profile_projects.content.intro')).'</p>'
                                    .'<p>'.e(__('profile_projects.content.optional')).'</p>'
                                    .'</div>'
                                ))
                                ->extraAttributes(['class' => 'profile-project-content-intro-field']),

                            Builder::make('content_blocks')
                                ->hiddenLabel()
                                ->addActionLabel(__('profile_projects.fields.content_block_add'))
                                ->addAction(fn (Action $action): Action => $action
                                    ->icon('heroicon-m-plus')
                                    ->iconPosition('after'))
                                ->addBetweenActionLabel(__('profile_projects.fields.content_block_add_between'))
                                ->addBetweenAction(fn (Action $action): Action => $action
                                    ->icon('heroicon-m-plus')
                                    ->iconPosition('after'))
                                ->moveUpAction(fn (Action $action): Action => $action->color('primary'))
                                ->moveDownAction(fn (Action $action): Action => $action->color('primary'))
                                ->deleteAction(fn (Action $action): Action => $action->color('primary'))
                                ->blocks([
                                    Block::make('heading')
                                        ->label(fn (?array $state): string => filled($state['heading_text'] ?? null)
                                            ? $state['heading_text']
                                            : __('profile_projects.fields.content_block_type_heading'))
                                        ->icon('heroicon-o-bookmark')
                                        ->extraAttributes(['class' => 'profile-project-content-block profile-project-content-block-heading'])
                                        ->schema([
                                            TextInput::make('heading_text')
                                                ->hiddenLabel()
                                                ->placeholder(__('profile_projects.fields.content_block_type_heading'))
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
                                        ->extraAttributes(['class' => 'profile-project-content-block profile-project-content-block-paragraph'])
                                        ->schema([
                                            Textarea::make('paragraph_text')
                                                ->hiddenLabel()
                                                ->placeholder(__('profile_projects.fields.content_block_type_paragraph'))
                                                ->required()
                                                ->rows(5)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),

                                    Block::make('image')
                                        ->label(__('profile_projects.fields.content_block_type_image'))
                                        ->icon('heroicon-o-photo')
                                        ->extraAttributes(['class' => 'profile-project-content-block profile-project-content-block-image'])
                                        ->schema([
                                            FileUpload::make('image')
                                                ->hiddenLabel()
                                                ->image()
                                                ->imageEditor()
                                                ->panelLayout('integrated')
                                                ->panelAspectRatio('4:3')
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
                                ->blockLabels(false)
                                ->blockIcons(false)
                                ->reorderableWithButtons()
                                ->reorderableWithDragAndDrop(false)
                                ->collapsible(false)
                                ->extraAttributes(['class' => 'profile-project-content-builder'])
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-content-builder-field']),
                        ]),

                    Step::make(__('profile_projects.tabs.parameters'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-parameters'])
                        ->disabled(fn (?Project $record): bool => self::cannotEditPublishedFields($record))
                        ->schema([
                            Section::make(__('profile_projects.sections.parameters.title'))
                                ->description(__('profile_projects.sections.parameters.description'))
                                ->schema([
                                    Placeholder::make('category_required_for_parameters')
                                        ->label('')
                                        ->content(__('profile_projects.fields.parameter_placeholder'))
                                        ->visible(fn (callable $get): bool => ! filled($get('art_category_id'))),

                                    Repeater::make('project_parameter_values')
                                        ->hiddenLabel()
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
                                                    ->native(false)
                                                    ->searchable()
                                                    ->extraFieldWrapperAttributes(['class' => 'profile-project-parameter-select'])
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
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-stages'])
                        ->disabled(fn (?Project $record): bool => $record?->exists === true && ! $record->canManageStagesByOwner())
                        ->schema([
                            Placeholder::make('stages_intro')
                                ->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div class="profile-project-stages-intro">'
                                    .'<p>'.e(__('profile_projects.stages.schedule')).'</p>'
                                    .'<p>'.e(__('profile_projects.stages.description')).'</p>'
                                    .'<p>'.e(__('profile_projects.stages.visibility')).'</p>'
                                    .'<div class="profile-project-stages-intro-group">'
                                    .'<p>'.e(__('profile_projects.stages.example')).'</p>'
                                    .'<p>'.e(__('profile_projects.stages.example_stage')).'</p>'
                                    .'</div>'
                                    .'<p>'.e(__('profile_projects.stages.order')).'</p>'
                                    .'<p>'.e(__('profile_projects.stages.payment')).'</p>'
                                    .'<p>'.e(__('profile_projects.stages.notifications')).'</p>'
                                    .'<p>'.e(__('profile_projects.stages.settlement')).'</p>'
                                    .'<p>'.e(__('profile_projects.stages.completion')).'</p>'
                                    .'</div>'
                                ))
                                ->extraAttributes(['class' => 'profile-project-stages-intro-field']),

                            Repeater::make('stages')
                                ->label(__('profile_projects.stages.new_stage'))
                                ->relationship()
                                ->orderColumn('order')
                                ->schema([
                                    TextInput::make('title')
                                        ->label(__('profile_projects.fields.stage_title'))
                                        ->placeholder(__('profile_projects.placeholders.stage_title'))
                                        ->extraFieldWrapperAttributes(['class' => 'profile-project-stage-title'])
                                        ->required()
                                        ->disabled(fn (?ProjectStage $record): bool => $record?->exists === true
                                            && $record->status !== StageStatus::Planned)
                                        ->columnSpanFull(),

                                    Textarea::make('description')
                                        ->label(__('profile_projects.fields.stage_description'))
                                        ->placeholder(__('profile_projects.placeholders.stage_description'))
                                        ->rows(2)
                                        ->autosize()
                                        ->extraFieldWrapperAttributes(['class' => 'profile-project-stage-description'])
                                        ->disabled(fn (?ProjectStage $record): bool => $record?->exists === true
                                            && $record->status !== StageStatus::Planned)
                                        ->columnSpanFull(),

                                    Radio::make('status')
                                        ->label(__('profile_projects.fields.stage_status'))
                                        ->helperText(__('profile_projects.helpers.stage_status'))
                                        ->options(StageStatus::getOptions())
                                        ->inline()
                                        ->extraFieldWrapperAttributes(['class' => 'profile-project-stage-status'])
                                        ->columnSpanFull()
                                        ->default(StageStatus::Planned->value)
                                        ->required()
                                        ->live()
                                        // Для збереженого етапу дозволений лише наступний послідовний статус.
                                        // Поточний статус порівнюємо з БД, а не зі станом поля, щоб після
                                        // одного кліка не відкрити можливість перескочити ще на один крок.
                                        ->disableOptionWhen(function (string $value, ?ProjectStage $record): bool {
                                            $candidate = StageStatus::from($value);

                                            if (! $record?->exists) {
                                                return $candidate !== StageStatus::Planned;
                                            }

                                            $current = $record->status;

                                            return $candidate->order() < $current->order()
                                                || $candidate->order() > $current->order() + 1;
                                        })
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

                                    TextInput::make('days_planned')
                                        ->label(__('profile_projects.fields.stage_days_planned'))
                                        ->numeric()
                                        ->minValue(1)
                                        ->disabled(fn (?ProjectStage $record): bool => $record?->exists === true
                                            && $record->status !== StageStatus::Planned)
                                        ->columnSpan(fn (callable $get): int => $get('status') === StageStatus::Completed->value ? 2 : 3),

                                    TextInput::make('budget_planned')
                                        ->label(__('profile_projects.fields.stage_budget_planned'))
                                        ->numeric()
                                        ->default(0)
                                        ->required(fn (callable $get): bool => $get('status') === StageStatus::Planned->value)
                                        ->disabled(fn (?ProjectStage $record): bool => $record?->exists === true
                                            && $record->status !== StageStatus::Planned)
                                        ->columnSpan(fn (callable $get): int => $get('status') === StageStatus::Completed->value ? 2 : 3),

                                    TextInput::make('budget_actual')
                                        ->label(__('profile_projects.fields.stage_budget_actual'))
                                        ->numeric()
                                        ->visible(fn (callable $get): bool => $get('status') === StageStatus::Completed->value)
                                        ->required(fn (callable $get): bool => $get('status') === StageStatus::Completed->value)
                                        ->columnSpan(2),

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
                                        ->extraFieldWrapperAttributes(['class' => 'profile-project-stage-documents'])
                                        ->columnSpanFull()
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

                                    Placeholder::make('stage_edit_notice')
                                        ->hiddenLabel()
                                        ->content(new HtmlString(
                                            '<div class="profile-project-stage-notice">'
                                            .'<p>'.e(__('profile_projects.stages.add_notice')).'</p>'
                                            .'<p>'.e(__('profile_projects.stages.edit_notice')).'</p>'
                                            .'</div>'
                                        ))
                                        ->visible(fn (?ProjectStage $record): bool => $record?->exists !== true)
                                        ->columnSpanFull(),
                                ])
                                ->columns(6)
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-stages-repeater'])
                                ->columnSpanFull()
                                ->defaultItems(0)
                                ->deletable(fn (?Project $record): bool => $record?->exists !== true || $record->canBeFullyEditedByOwner())
                                ->reorderable(fn (?Project $record): bool => $record?->exists !== true || $record->canBeFullyEditedByOwner())
                                ->addAction(fn (Action $action): Action => $action
                                    ->label(__('profile_projects.actions.add_stage'))
                                    ->icon('heroicon-m-plus')
                                    ->iconButton())
                                ->collapsible()
//                                ->collapsed(fn (?Project $record): bool => $record?->exists === true)
                                ->itemLabel(function (array $state): string|Htmlable {
                                    $title = e($state['title'] ?? __('profile_projects.defaults.stage_title'));
                                    $status = StageStatus::tryFrom($state['status'] ?? '');

                                    if (! $status) {
                                        return $title;
                                    }

                                    // Tailwind-скидання ставить svg { display: block }, тож без
                                    // inline-flex-обгортки іконка "падає" під текст на новий рядок.
                                    // Колір — через CSS-змінну кольору Filament (svg stroke="currentColor").
                                    $icon = generate_icon_html($status->getIcon())?->toHtml();
                                    $color = e($status->getColor());

                                    return new HtmlString(
                                        '<span style="display:inline-flex;align-items:center;gap:0.375rem">'
                                        ."<span style=\"color:var(--{$color}-500)\">{$icon}</span>"
                                        ."<span>{$title} — ".e($status->getLabel()).'</span>'
                                        .'</span>'
                                    );
                                }),
                        ]),

                    Step::make(__('profile_projects.tabs.bonuses'))
                        ->icon('heroicon-o-gift')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-bonuses'])
                        ->disabled(fn (?Project $record): bool => $record?->exists === true && ! $record->canManageBonusesByOwner())
                        ->schema([
                            Placeholder::make('bonuses_intro')
                                ->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div class="profile-project-bonuses-intro">'
                                    .'<div class="profile-project-bonuses-examples">'
                                    .'<p>'.e(__('profile_projects.bonuses.intro')).'</p>'
                                    .'<p>'.e(__('profile_projects.bonuses.example_print')).'</p>'
                                    .'<p>'.e(__('profile_projects.bonuses.example_premiere')).'</p>'
                                    .'<p>'.e(__('profile_projects.bonuses.example_album')).'</p>'
                                    .'</div>'
                                    .'<p>'.e(__('profile_projects.bonuses.support_hint')).'</p>'
                                    .'<p>'.e(__('profile_projects.bonuses.optional')).'</p>'
                                    .'</div>'
                                ))
                                ->extraAttributes(['class' => 'profile-project-bonuses-intro-field']),

                            Repeater::make('bonuses')
                                ->hiddenLabel()
                                ->relationship()
                                ->schema([
                                    Hidden::make('order')
                                        ->default(0),

                                    Hidden::make('title')
                                        ->default(__('profile_projects.defaults.bonus_title')),

                                    Placeholder::make('support_amount_heading')
                                        ->hiddenLabel()
                                        ->content(__('profile_projects.bonuses.support_amount'))
                                        ->extraAttributes(['class' => 'profile-project-bonus-range-heading'])
                                        ->columnSpanFull(),

                                    TextInput::make('min_donation')
                                        ->label(__('profile_projects.fields.bonus_min_donation'))
                                        ->placeholder('0')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('max_donation')
                                        ->label(__('profile_projects.fields.bonus_max_donation'))
                                        ->placeholder('0')
                                        ->numeric()
                                        ->gt('min_donation')
                                        ->columnSpan(1),

                                    TextInput::make('quantity')
                                        ->label(__('profile_projects.fields.bonus_quantity'))
                                        ->prefixIcon('heroicon-s-gift')
                                        ->numeric()
                                        ->placeholder(__('profile_projects.placeholders.bonus_quantity'))
                                        ->columnSpan(1),

                                    Textarea::make('description')
                                        ->label(__('profile_projects.fields.bonus_description'))
                                        ->placeholder(__('profile_projects.placeholders.bonus_description'))
                                        ->rows(1)
                                        ->autosize()
                                        ->columnSpanFull(),
                                ])
                                ->columns(3)
                                ->columnSpanFull()
                                ->defaultItems(0)
                                ->addActionLabel(__('profile_projects.actions.add_bonus'))
                                ->addAction(fn (Action $action): Action => $action
                                    ->icon('heroicon-m-plus')
                                    ->iconPosition('after'))
                                ->deleteAction(fn (Action $action): Action => $action
                                    ->icon('heroicon-o-x-mark')
                                    ->color('primary'))
                                ->reorderable(false)
                                ->collapsible(false)
                                ->extraFieldWrapperAttributes(['class' => 'profile-project-bonuses-repeater']),
                        ]),

                    Step::make(__('profile_projects.tabs.publication'))
                        ->icon('heroicon-o-eye')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-publication'])
                        ->schema([
                            Placeholder::make('publication_preview')
                                ->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div class="profile-project-publication-copy">'
                                    .'<strong>'.e(__('profile_projects.publication.title')).'</strong>'
                                    .'<p>'.e(__('profile_projects.publication.description')).'</p>'
                                    .'</div>'
                                )),

                            SchemaActions::make([
                                Action::make('previewProject')
                                    ->label(__('profile_projects.actions.preview_project'))
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->iconPosition('after')
                                    ->extraAttributes([
                                        'class' => 'profile-project-preview-action',
                                        'onclick' => "window.open('about:blank', 'project-preview')",
                                    ])
                                    ->action('previewProject'),
                            ])
                                ->extraAttributes(['class' => 'profile-project-preview-actions']),
                        ]),

                    Step::make(__('profile_projects.tabs.final_result'))
                        ->icon('heroicon-o-trophy')
                        ->extraAttributes(['class' => 'profile-project-step profile-project-step-final-result'])
                        // Фінальний результат має сенс лише тоді, коли проєкт реально
                        // виконується або вже завершений — на чернетці/модерації показувати
                        // немає чого.
                        ->visible(fn (?Project $record): bool => $record?->canEditFinalResultByOwner() === true)
                        ->schema([
                            Builder::make('final_result')
                                ->label(__('profile_projects.fields.final_result'))
                                ->addActionLabel(__('profile_projects.fields.final_result_add'))
                                ->blocks([
                                    Block::make('gallery')
                                        ->label(__('profile_projects.fields.final_result_type_gallery'))
                                        ->icon('heroicon-o-photo')
                                        ->schema([
                                            FileUpload::make('images')
                                                ->label(__('profile_projects.fields.final_result_gallery_images'))
                                                ->image()
                                                ->multiple()
                                                ->reorderable()
                                                ->panelLayout('grid')
                                                ->downloadable()
                                                ->openable()
                                                ->extraAttributes(['class' => 'fi-fo-file-upload-grid-compact'])
                                                ->extraFieldWrapperAttributes(['class' => 'profile-project-stage-documents profile-project-final-result-files'])
                                                ->disk('public')
                                                ->directory('projects/final-result')
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                ->maxSize(5120)
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),

                                    Block::make('youtube')
                                        ->label(__('profile_projects.fields.final_result_type_youtube'))
                                        ->icon('heroicon-o-play-circle')
                                        ->schema([
                                            TextInput::make('url')
                                                ->label(__('profile_projects.fields.final_result_youtube_url'))
                                                ->url()
                                                ->regex('/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\//i')
                                                ->placeholder('https://www.youtube.com/watch?v=...')
                                                ->required()
                                                ->maxLength(500)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),

                                    Block::make('vimeo')
                                        ->label(__('profile_projects.fields.final_result_type_vimeo'))
                                        ->icon('heroicon-o-film')
                                        ->schema([
                                            TextInput::make('url')
                                                ->label(__('profile_projects.fields.final_result_vimeo_url'))
                                                ->url()
                                                ->regex('/^https?:\/\/(www\.)?vimeo\.com\//i')
                                                ->placeholder('https://vimeo.com/...')
                                                ->required()
                                                ->maxLength(500)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),

                                    Block::make('issuu')
                                        ->label(__('profile_projects.fields.final_result_type_issuu'))
                                        ->icon('heroicon-o-book-open')
                                        ->schema([
                                            TextInput::make('url')
                                                ->label(__('profile_projects.fields.final_result_issuu_url'))
                                                ->url()
                                                ->regex('/^https?:\/\/(www\.)?issuu\.com\//i')
                                                ->placeholder('https://issuu.com/...')
                                                ->required()
                                                ->maxLength(500)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1),
                                ])
                                ->columnSpanFull()
                                ->blockNumbers(false)
                                ->reorderable()
                                ->collapsed(false),
                        ]),
                ]))
                    ->extraAttributes(['class' => 'profile-project-form-wizard'])
                    ->columnSpanFull()
                    ->persistStepInQueryString()
                    ->nextAction(fn (Action $action): Action => $action
                        ->label(__('profile_projects.actions.next'))
                        ->icon('heroicon-m-chevron-right')
                        ->extraAttributes(['class' => 'profile-project-wizard-next']))
                    ->previousAction(fn (Action $action): Action => $action
                        ->label(__('profile_projects.actions.back'))
                        ->icon('heroicon-m-chevron-left')
                        ->extraAttributes(['class' => 'profile-project-wizard-previous']))
                    // При редагуванні існуючого проєкту дозволяємо вільно перемикатись
                    // між кроками клацанням, як у табах — усі дані вже заповнені, тому
                    // послідовна валідація "Далі/Назад" тут тільки заважає. При створенні
                    // нового проєкту крокова навігація лишається (record ще не існує).
                    ->skippable(fn (?Project $record): bool => $record !== null),
            ]);
    }

    /**
     * @param  array<int, Step>  $steps
     * @return array<int, Step>
     */
    private static function orderedWizardSteps(array $steps): array
    {
        return [
            $steps[0], // Автор
            $steps[1], // Назва
            $steps[2], // Опис
            $steps[5], // Характеристики
            $steps[3], // Бюджет
            $steps[6], // Етапи реалізації
            $steps[4], // Додаткова інформація
            $steps[7], // Бонуси
            $steps[8], // Публікація
            $steps[9], // Фінальний результат (видимий лише для активних/завершених)
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function projectOwnerOptions(): array
    {
        $user = auth()->user()?->loadMissing('profileLegal');
        $options = [
            'personal' => $user?->display_name ?? __('profile_projects.defaults.personal_owner'),
        ];

        if (filled($user?->profileLegal?->name)) {
            $options['legal'] = $user->profileLegal->name;
        }

        return $options + self::projectOwnerTeams()
            ->mapWithKeys(fn (Team $team) => [
                'team:'.$team->getKey() => $team->getAttribute('name')['uk'] ?? $team->slug,
            ])
            ->toArray();
    }

    private static function projectOwnerAvatarStyles(): string
    {
        $user = auth()->user()?->loadMissing('profileLegal');
        $avatarUrls = [$user?->getFilamentAvatarUrl()];

        if (filled($user?->profileLegal?->name)) {
            $avatarUrls[] = filled($user->profileLegal->logo)
                ? Storage::disk('public')->url($user->profileLegal->logo)
                : asset('img/legal_entity.webp');
        }

        foreach (self::projectOwnerTeams() as $team) {
            $avatarUrls[] = filled($team->avatar)
                ? Storage::disk('public')->url($team->avatar)
                : asset('img/legal_entity.webp');
        }

        return collect($avatarUrls)
            ->map(function (?string $url, int $index): string {
                $url ??= asset('img/legal_entity.webp');
                $url = str_replace(["'", '"', "\n", "\r"], ['%27', '%22', '', ''], $url);

                return '--profile-project-owner-avatar-'.($index + 1).": url('{$url}')";
            })
            ->implode('; ');
    }

    /**
     * @return Collection<int, Team>
     */
    private static function projectOwnerTeams(): Collection
    {
        return Team::query()
            ->whereHas('teamMembers', fn ($query) => $query->where('user_id', auth()->id()))
            ->orderBy('id')
            ->get();
    }
}
