<?php

namespace App\Filament\Profile\Resources\Teams\Schemas;

use App\Models\User;
use App\Support\Countries;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

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
                    ->extraFieldWrapperAttributes(['class' => 'team-form-cover-field'])
                    ->required(),

                TextInput::make('name')
                    ->label(__('profile_teams.fields.name'))
                    ->placeholder(__('profile_teams.placeholders.name'))
                    ->required()
                    ->maxLength(255),

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
                            ->required(),

                        TextInput::make('city')
                            ->label(__('profile_teams.fields.city'))
                            ->placeholder(__('profile_teams.placeholders.city'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('region')
                            ->label(__('profile_teams.fields.region'))
                            ->placeholder(__('profile_teams.placeholders.region'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('zip')
                            ->label(__('profile_teams.fields.zip'))
                            ->placeholder(__('profile_teams.placeholders.zip'))
                            ->required()
                            ->maxLength(20),
                    ]),

                TextInput::make('specialization')
                    ->label(__('profile_teams.fields.specialization'))
                    ->placeholder(__('profile_teams.placeholders.specialization'))
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label(__('profile_teams.fields.description'))
                    ->placeholder(__('profile_teams.placeholders.description'))
                    ->required()
                    ->maxLength(5000)
                    ->rows(7),

                Repeater::make('teamMembers')
                    ->label(__('profile_teams.sections.members'))
                    ->relationship('teamMembers', modifyQueryUsing: fn ($query) => $query->where('role', 'member'))
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'role' => 'member'])
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [...$data, 'role' => 'member'])
                    ->orderColumn('sort_order')
                    ->schema([
                        Select::make('user_id')
                            ->hiddenLabel()
                            ->placeholder(__('profile_teams.placeholders.member'))
                            ->searchable()
                            ->native(false)
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
                            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->full_name)
                            ->required(),
                    ])
                    ->reorderableWithDragAndDrop()
                    ->addActionLabel(__('profile_teams.placeholders.member'))
                    ->extraFieldWrapperAttributes(['class' => 'team-form-members']),
            ]);
    }
}
