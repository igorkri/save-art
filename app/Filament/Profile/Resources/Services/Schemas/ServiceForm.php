<?php

namespace App\Filament\Profile\Resources\Services\Schemas;

use App\Enums\Currency;
use App\Models\ArtCategory;
use App\Models\Team;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('profile_services.sections.owner'))
                    ->columns(2)
                    ->schema([
                        Radio::make('owner_type')
                            ->label(__('profile_services.fields.owner_type'))
                            ->options([
                                'personal' => __('profile_services.fields.owner_personal'),
                                'team' => __('profile_services.fields.owner_team'),
                            ])
                            ->default('personal')
                            ->live()
                            ->disabledOn('edit')
                            ->required(),

                        Select::make('team_id')
                            ->label(__('profile_services.fields.team'))
                            ->options(fn () => Team::query()
                                ->whereHas('teamMembers', fn ($query) => $query->where('user_id', auth()->id()))
                                ->get()
                                ->mapWithKeys(fn (Team $team) => [$team->id => $team->getAttribute('name')['uk'] ?? $team->slug])
                                ->toArray())
                            ->visible(fn (Get $get): bool => $get('owner_type') === 'team')
                            ->disabledOn('edit')
                            ->required(fn (Get $get): bool => $get('owner_type') === 'team'),
                    ]),

                Section::make(__('profile_services.sections.main'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('profile_services.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('description')
                            ->label(__('profile_services.fields.description'))
                            ->columnSpanFull(),
                        TextInput::make('location')
                            ->label(__('profile_services.fields.location'))
                            ->columnSpanFull(),

                        Select::make('art_category_id')
                            ->label(__('profile_services.fields.art_category'))
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
                            ->searchable(),

                        FileUpload::make('image')
                            ->label(__('profile_services.fields.image'))
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('services')
                            ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file)),
                    ]),

                Section::make(__('profile_services.sections.price'))
                    ->columns(3)
                    ->schema([
                        Select::make('currency')
                            ->label(__('profile_services.fields.currency'))
                            ->options([
                                Currency::UAH->value => '₴ UAH',
                                Currency::USD->value => '$ USD',
                                Currency::EUR->value => '€ EUR',
                            ])
                            ->default(Currency::UAH->value)
                            ->required(),

                        TextInput::make('price')
                            ->label(__('profile_services.fields.price'))
                            ->numeric(),

                        Toggle::make('price_from')
                            ->label(__('profile_services.fields.price_from')),
                    ]),
            ]);
    }
}
