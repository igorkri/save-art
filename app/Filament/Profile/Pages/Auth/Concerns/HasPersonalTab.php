<?php

namespace App\Filament\Profile\Pages\Auth\Concerns;

use App\Support\Countries;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;

trait HasPersonalTab
{
    private function personalTab(): Tab
    {
        return Tab::make(__('profile_edit.tabs.personal'))
            ->key('personal')
            ->icon('heroicon-o-user')
            ->schema([
                Section::make(__('profile_edit.sections.personal.title'))
                    ->description(__('profile_edit.sections.personal.description'))
                    ->extraAttributes(['class' => 'profile-edit-content'])
                    ->schema([
                        FileUpload::make('avatar')
                            ->label(__('profile_edit.fields.avatar'))
                            ->avatar()
                            ->required()
                            ->image()
                            ->imageCropAspectRatio('1:1')
                            ->imageEditor()
                            ->imagePreviewHeight('180')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('compact')
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('avatars')
                            ->extraFieldWrapperAttributes(['class' => 'profile-primary-image-field profile-edit-avatar-field'])
                            ->belowContent(
                                Text::make(__('profile_edit.helpers.avatar'))
                                    ->extraAttributes(['class' => '!flex !w-full justify-center text-center'])
                            )
                            ->alignCenter(),
                        TextInput::make('full_name')
                            ->label(__('profile_edit.fields.full_name'))
                            ->required()
                            ->placeholder(__('profile_edit.placeholders.full_name'))
                            ->maxLength(255),
                        TextInput::make('profession')
                            ->label(__('profile_edit.fields.profession'))
                            ->placeholder(__('profile_edit.placeholders.profession'))
                            ->required()
                            ->maxLength(255),
                        TagsInput::make('tags')
                            ->label(__('profile_edit.fields.tags'))
                            ->placeholder(__('profile_edit.placeholders.tags'))
                            ->helperText(__('profile_edit.helpers.tags'))
                            ->afterStateHydrated(fn (TagsInput $component, array|string|null $state) => $component->state(
                                is_string($state) ? array_map('trim', explode(',', $state)) : ($state ?? []),
                            )),
                        Select::make('country')
                            ->label(__('profile_edit.fields.country'))
                            ->options(Countries::options())
                            ->searchable()
                            ->required()
                            ->default('Україна'),
                        TextInput::make('region')
                            ->label(__('profile_edit.fields.region'))
                            ->placeholder(__('profile_edit.placeholders.region'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label(__('profile_edit.fields.city'))
                            ->placeholder(__('profile_edit.placeholders.city'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label(__('profile_edit.fields.postal_code'))
                            ->placeholder(__('profile_edit.placeholders.postal_code'))
                            ->maxLength(20),
                        TextInput::make('phone')
                            ->label(__('profile_edit.fields.phone'))
                            ->required()
                            ->tel()
                            ->telRegex('/^\+[1-9]\d{6,14}$/')
                            ->placeholder(__('profile_edit.placeholders.phone'))
                            ->helperText(__('profile_edit.helpers.phone'))
                            ->maxLength(50),
                        Textarea::make('description')
                            ->label(__('profile_edit.fields.description'))
                            ->placeholder(__('profile_edit.placeholders.description'))
                            ->rows(6)
                            ->autosize()
                            ->maxLength(10000)
                            ->required(),
                    ]),
            ]);
    }
}
