<?php

namespace App\Filament\Profile\Resources\Teams\Schemas;

use App\Models\Team;
use App\Models\User;
use App\Support\Countries;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component as LivewireComponent;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'team-form'])
            ->components([
                FileUpload::make('avatar')
                    ->hiddenLabel()
                    ->placeholder(__('profile_teams.placeholders.avatar'))
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->imagePreviewHeight('400')
                    ->panelAspectRatio('1:1')
                    ->panelLayout('compact')
                    ->maxSize(5120)
                    ->disk('public')
                    ->directory('teams')
                    ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                    ->extraFieldWrapperAttributes(['class' => 'team-form-cover-field profile-primary-image-field'])
                    ->required()
                    ->live()
                    ->afterStateUpdated(self::validateOnUpdate()),

                TextInput::make('name')
                    ->label(__('profile_teams.fields.name'))
                    ->placeholder(__('profile_teams.placeholders.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::validateOnUpdate()),

                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])
                    ->extraAttributes(['class' => 'team-form-location-grid'])
                    ->schema([
                        Select::make('country')
                            ->label(__('profile_teams.fields.country'))
                            ->placeholder(__('profile_teams.placeholders.country'))
                            ->options(Countries::options())
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(self::validateOnUpdate()),

                        TextInput::make('city')
                            ->label(__('profile_teams.fields.city'))
                            ->placeholder(__('profile_teams.placeholders.city'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(self::validateOnUpdate()),

                        TextInput::make('region')
                            ->label(__('profile_teams.fields.region'))
                            ->placeholder(__('profile_teams.placeholders.region'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(self::validateOnUpdate()),

                        TextInput::make('zip')
                            ->label(__('profile_teams.fields.zip'))
                            ->placeholder(__('profile_teams.placeholders.zip'))
                            ->required()
                            ->maxLength(20)
                            ->live(onBlur: true)
                            ->afterStateUpdated(self::validateOnUpdate()),
                    ]),

                TextInput::make('specialization')
                    ->label(__('profile_teams.fields.specialization'))
                    ->placeholder(__('profile_teams.placeholders.specialization'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::validateOnUpdate()),

                Textarea::make('description')
                    ->label(__('profile_teams.fields.description'))
                    ->placeholder(__('profile_teams.placeholders.description'))
                    ->required()
                    ->maxLength(5000)
                    ->rows(7)
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::validateOnUpdate()),

                Select::make('member_ids')
                    ->label(__('profile_teams.sections.members'))
                    ->placeholder(__('profile_teams.placeholders.member'))
                    ->multiple()
                    ->reorderable()
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->afterStateUpdatedJs('const searchInput = $el.querySelector(".fi-select-input-search-ctn .fi-input"); if (searchInput) { searchInput.value = ""; searchInput.dispatchEvent(new Event("input", { bubbles: true })); }')
                    ->suffixIcon('heroicon-s-magnifying-glass')
                    ->suffixIconColor('primary')
                    ->getSearchResultsUsing(fn (string $search): array => User::query()
                        ->where('is_blocked', false)
                        ->where('id', '!=', auth()->id())
                        ->where(fn ($query) => $query->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->limit(20)
                        ->pluck('full_name', 'id')
                        ->toArray())
                    ->getOptionLabelsUsing(fn (array $values): array => User::query()
                        ->whereIn('id', $values)
                        ->pluck('full_name', 'id')
                        ->toArray())
                    ->loadStateFromRelationshipsUsing(function (Select $component): void {
                        $record = $component->getRecord();

                        if (! $record instanceof Team) {
                            return;
                        }

                        $component->state(
                            $record->teamMembers()
                                ->where('role', 'member')
                                ->pluck('user_id')
                                ->map(static fn (int $id): string => (string) $id)
                                ->all(),
                        );
                    })
                    ->saveRelationshipsUsing(function (Select $component, ?array $state): void {
                        $record = $component->getRecord();

                        if (! $record instanceof Team) {
                            return;
                        }

                        $ownerIds = $record->teamMembers()
                            ->where('role', 'owner')
                            ->pluck('user_id');

                        $memberIds = User::query()
                            ->where('is_blocked', false)
                            ->where('id', '!=', auth()->id())
                            ->whereIn('id', $state ?? [])
                            ->pluck('id')
                            ->diff($ownerIds)
                            ->values();

                        $membersQuery = $record->teamMembers()->where('role', 'member');

                        if ($memberIds->isEmpty()) {
                            $membersQuery->delete();
                        } else {
                            $membersQuery
                                ->whereNotIn('user_id', $memberIds)
                                ->delete();
                        }

                        foreach ($memberIds as $index => $userId) {
                            $record->teamMembers()->updateOrCreate(
                                ['user_id' => $userId],
                                ['role' => 'member', 'sort_order' => $index + 1],
                            );
                        }
                    })
                    ->default([])
                    ->extraFieldWrapperAttributes([
                        'class' => 'team-form-members',
                        'x-on:click.capture' => <<<'JS'
                            const badges = $event.target.closest('.fi-select-input-value-badges-ctn');
                            const removeButton = $event.target.closest('.fi-badge-delete-btn');

                            if (badges && ! removeButton) {
                                const selectButton = $el.querySelector('.fi-select-input-btn');

                                if (selectButton?.getAttribute('aria-expanded') === 'false') {
                                    selectButton.click();
                                }
                            }
                            JS,
                    ]),
            ]);
    }

    private static function validateOnUpdate(): Closure
    {
        return static function (Field $component, LivewireComponent $livewire): void {
            $livewire->validateOnly($component->getStatePath());
        };
    }
}
