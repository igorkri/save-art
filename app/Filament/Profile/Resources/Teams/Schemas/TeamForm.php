<?php

namespace App\Filament\Profile\Resources\Teams\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('profile_teams.sections.main'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.uk')
                            ->label(__('profile_teams.fields.name_uk'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name.en')
                            ->label(__('profile_teams.fields.name_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('website')
                            ->label(__('profile_teams.fields.website'))
                            ->url()
                            ->maxLength(255),

                        FileUpload::make('avatar')
                            ->label(__('profile_teams.fields.avatar'))
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('teams')
                            ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('country.uk')
                            ->label(__('profile_teams.fields.country_uk'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('country.en')
                            ->label(__('profile_teams.fields.country_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('city.uk')
                            ->label(__('profile_teams.fields.city_uk'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('city.en')
                            ->label(__('profile_teams.fields.city_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('region.uk')
                            ->label(__('profile_teams.fields.region_uk'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('region.en')
                            ->label(__('profile_teams.fields.region_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('zip.uk')
                            ->label(__('profile_teams.fields.zip_uk'))
                            ->required()
                            ->maxLength(20),
                        TextInput::make('zip.en')
                            ->label(__('profile_teams.fields.zip_en'))
                            ->required()
                            ->maxLength(20),

                        TextInput::make('specialization.uk')
                            ->label(__('profile_teams.fields.specialization_uk'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('specialization.en')
                            ->label(__('profile_teams.fields.specialization_en'))
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description.uk')
                            ->label(__('profile_teams.fields.description_uk'))
                            ->required()
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Textarea::make('description.en')
                            ->label(__('profile_teams.fields.description_en'))
                            ->required()
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('profile_teams.sections.members'))
                    ->schema([
                        Repeater::make('teamMembers')
                            ->label('')
                            ->relationship('teamMembers', modifyQueryUsing: fn ($query) => $query->where('role', 'member'))
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'role' => 'member'])
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [...$data, 'role' => 'member'])
                            ->orderColumn('sort_order')
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('profile_teams.fields.member'))
                                    ->searchable()
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
                            ->addActionLabel(__('profile_teams.actions.add_member'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
