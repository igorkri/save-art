<?php

namespace App\Filament\Profile\Resources\Services\Schemas;

use App\Enums\Currency;
use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\Team;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component as LivewireComponent;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'service-form'])
            ->components([
                Section::make(__('profile_services.sections.additional'))
                    ->columns(2)
                    ->collapsed()
                    ->extraAttributes(['class' => 'service-form-additional'])
                    ->schema([
                        Radio::make('owner_type')
                            ->label(__('profile_services.fields.owner_type'))
                            ->options([
                                'personal' => __('profile_services.fields.owner_personal'),
                                'team' => __('profile_services.fields.owner_team'),
                            ])
                            ->default('personal')
                            ->live()
                            ->afterStateUpdated(self::validateOnUpdate())
                            ->disabledOn('edit')
                            ->columnStart(['md' => 1])
                            ->required(),

                        Select::make('team_id')
                            ->label(__('profile_services.fields.team'))
                            ->options(fn () => Team::query()
                                ->whereHas('teamMembers', fn ($query) => $query->where('user_id', auth()->id()))
                                ->get()
                                ->mapWithKeys(fn (Team $team) => [$team->id => $team->getAttribute('name')['uk'] ?? $team->slug])
                                ->toArray())
                            ->visible(fn (Get $get): bool => $get('owner_type') === 'team')
                            ->live()
                            ->afterStateUpdated(self::validateOnUpdate())
                            ->disabledOn('edit')
                            ->columnStart(['md' => 2])
                            ->required(fn (Get $get): bool => $get('owner_type') === 'team'),

                        Select::make('art_category_id')
                            ->label(__('profile_services.fields.art_category'))
                            ->options(self::artCategoryOptions())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(self::validateOnUpdate())
                            ->required()
                            ->columnStart(['md' => 1])
                            ->native(false),

                        TextInput::make('location')
                            ->label(__('profile_services.fields.location'))
                            ->columnStart(['md' => 2])
                            ->placeholder(__('profile_services.placeholders.location')),
                    ]),

                FileUpload::make('image')
                    ->hiddenLabel()
                    ->placeholder(__('profile_services.placeholders.image'))
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->imagePreviewHeight('400')
                    ->panelAspectRatio('1:1')
                    ->panelLayout('compact')
                    ->maxSize(5120)
                    ->disk('public')
                    ->directory('services')
                    ->required()
                    ->live()
                    ->afterStateUpdated(self::validateOnUpdate())
                    ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                    ->extraFieldWrapperAttributes(['class' => 'service-form-cover-field profile-primary-image-field']),

                TextInput::make('title')
                    ->label(__('profile_services.fields.title'))
                    ->placeholder(__('profile_services.placeholders.title'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::validateOnUpdate())
                    ->maxLength(255),

                Section::make(__('profile_services.sections.price'))
                    ->extraAttributes(['class' => 'service-form-price'])
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 12,
                        ])
                            ->extraAttributes(['class' => 'service-form-price-row'])
                            ->schema([
                                Checkbox::make('price_from')
                                    ->label(__('profile_services.fields.price_from'))
                                    ->disabled(fn (Get $get): bool => (bool) $get('negotiable'))
                                    ->dehydrated()
                                    ->dehydrateStateUsing(fn ($state, Get $get): bool => ! $get('negotiable') && (bool) $state)
                                    ->extraFieldWrapperAttributes(['class' => 'service-form-price-from'])
                                    ->columnSpan(['md' => 2]),

                                TextInput::make('price')
                                    ->hiddenLabel()
                                    ->placeholder(__('profile_services.placeholders.price'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(fn (Get $get): bool => ! $get('negotiable'))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(self::validateOnUpdate())
                                    ->disabled(fn (Get $get): bool => (bool) $get('negotiable'))
                                    ->dehydrated()
                                    ->dehydrateStateUsing(fn ($state, Get $get) => $get('negotiable') ? null : $state)
                                    ->extraFieldWrapperAttributes(['class' => 'service-form-price-input'])
                                    ->columnSpan(['md' => 6]),

                                Radio::make('currency')
                                    ->hiddenLabel()
                                    ->options([
                                        Currency::UAH->value => '₴',
                                        Currency::USD->value => '$',
                                        Currency::EUR->value => '€',
                                    ])
                                    ->default(Currency::UAH->value)
                                    ->inline()
                                    ->required(fn (Get $get): bool => ! $get('negotiable'))
                                    ->live()
                                    ->afterStateUpdated(self::validateOnUpdate())
                                    ->extraFieldWrapperAttributes(['class' => 'service-form-currency'])
                                    ->columnSpan(['md' => 4]),
                            ]),

                        Checkbox::make('negotiable')
                            ->label(__('profile_services.fields.negotiable'))
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(self::validatePriceOnNegotiableUpdate())
                            ->afterStateHydrated(fn (Checkbox $component, ?Service $record) => $component->state(
                                $record instanceof Service && $record->price === null,
                            ))
                            ->dehydrated(false)
                            ->extraFieldWrapperAttributes(['class' => 'service-form-negotiable']),
                    ]),

                Textarea::make('description')
                    ->label(__('profile_services.fields.description'))
                    ->placeholder(__('profile_services.placeholders.description'))
                    ->rows(7)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::validateOnUpdate())
                    ->autosize()
                    ->maxLength(5000),

                Repeater::make('options')
                    ->label(__('profile_services.fields.options'))
                    ->schema([
                        TextInput::make('name.uk')
                            ->hiddenLabel()
                            ->placeholder(__('profile_services.placeholders.option'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(self::validateOnUpdate())
                            ->maxLength(255),
                    ])
                    ->addActionLabel(__('profile_services.actions.add_option'))
                    ->deleteAction(fn (Action $action) => $action
                        ->icon('heroicon-o-x-mark')
                        ->color('primary'))
                    ->reorderable(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(self::validateOnUpdate())
                    ->maxItems(50)
                    ->defaultItems(1)
                    ->extraFieldWrapperAttributes(['class' => 'service-form-options']),
            ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function artCategoryOptions(): array
    {
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
    }

    private static function validateOnUpdate(): Closure
    {
        return static function (Field $component, LivewireComponent $livewire): void {
            $livewire->validateOnly($component->getStatePath());
        };
    }

    private static function validatePriceOnNegotiableUpdate(): Closure
    {
        return static function (LivewireComponent $livewire): void {
            $livewire->resetValidation(['data.price', 'data.currency']);
            $livewire->validateOnly('data.price');
            $livewire->validateOnly('data.currency');
        };
    }
}
