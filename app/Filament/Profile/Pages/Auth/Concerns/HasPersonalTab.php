<?php

namespace App\Filament\Profile\Pages\Auth\Concerns;

use App\Enums\ProfileType;
use App\Support\Countries;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
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
                Tabs::make('personal_sub')
                    ->persistTabInQueryString('personal_tab')
                    ->tabs([
                        $this->personalAccountTab(),
                        $this->personalAvatarTab(),
                        $this->personalAddressTab(),
                        $this->personalAboutTab(),
                    ]),
            ]);
    }

    private function personalAccountTab(): Tab
    {
        return Tab::make(__('profile_edit.sections.account.title'))
            ->key('personal_account')
            ->icon('heroicon-o-key')
            ->schema([
                Section::make(__('profile_edit.sections.account.title'))
                    ->description(__('profile_edit.sections.account.description'))
                    ->columns(2)
                    ->schema([
                        $this->getEmailFormComponent(),
                        Select::make('profile_type')
                            ->label(__('profile_edit.fields.profile_type'))
                            ->options(ProfileType::getOptions())
                            ->disabled()
                            ->dehydrated(false),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                        TextInput::make('phone')
                            ->label(__('profile_edit.fields.phone'))
                            ->required()
                            ->tel()
                            ->telRegex('/^\+[1-9]\d{6,14}$/')
                            ->placeholder(__('profile_edit.placeholders.phone'))
                            ->helperText(__('profile_edit.helpers.phone'))
                            ->maxLength(50),
                    ]),
            ]);
    }

    private function personalAvatarTab(): Tab
    {
        return Tab::make(__('profile_edit.sections.avatar.title'))
            ->key('personal_avatar')
            ->icon('heroicon-o-user-circle')
            ->schema([
                Section::make(__('profile_edit.sections.avatar.title'))
                    ->description(__('profile_edit.sections.avatar.description'))
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                Grid::make(1)
                                    ->schema([
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
                                    ])
                                    ->columnSpan(4),
                                FileUpload::make('avatar')
                                    ->label(__('profile_edit.fields.avatar'))
                                    ->avatar()
                                    ->required()
                                    ->image()
                                    ->imageCropAspectRatio('1:1')
                                    ->imageEditor()
                                    ->imagePreviewHeight('400')
                                    ->panelAspectRatio('1:1')
                                    ->panelLayout('compact')
                                    ->maxSize(5120)
                                    ->disk('public')
                                    ->directory('avatars')
                                    ->extraFieldWrapperAttributes(['class' => 'profile-primary-image-field'])
                                    ->belowContent(
                                        Text::make(__('profile_edit.helpers.avatar'))
                                            ->extraAttributes(['class' => '!flex !w-full justify-center text-center'])
                                    )
                                    ->alignCenter()
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }

    private function personalAddressTab(): Tab
    {
        return Tab::make(__('profile_edit.sections.address.title'))
            ->key('personal_address')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Section::make(__('profile_edit.sections.address.title'))
                    ->description(__('profile_edit.sections.address.description'))
                    ->columns(2)
                    ->schema([
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
                    ]),
            ]);
    }

    private function personalAboutTab(): Tab
    {
        return Tab::make(__('profile_edit.sections.about.title'))
            ->key('personal_about')
            ->icon('heroicon-o-pencil-square')
            ->schema([
                Section::make(__('profile_edit.sections.about.title'))
                    ->description(__('profile_edit.sections.about.description'))
                    ->schema([
                        Textarea::make('description')
                            ->label(__('profile_edit.fields.description'))
                            ->placeholder(__('profile_edit.placeholders.description'))
                            ->rows(6)
                            ->autosize()
                            ->maxLength(10000)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
