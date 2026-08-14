<?php

namespace App\Filament\Profile\Pages\Auth;

use App\Enums\Currency;
use App\Enums\ProfileType;
use App\Models\User;
use App\Support\Countries;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditProfile extends BaseEditProfile
{
    public function getTitle(): string
    {
        return __('profile_edit.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(false)
            ->components([
                Tabs::make('profile')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('profile_edit.tabs.personal'))
                            ->key('personal')
                            ->icon('heroicon-o-user')
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

                                Section::make(__('profile_edit.sections.avatar.title'))
                                    ->description(__('profile_edit.sections.avatar.description'))
                                    ->columns(2)
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
                                                    ->maxLength(255),
                                                TagsInput::make('tags')
                                                    ->label(__('profile_edit.fields.tags'))
                                                    ->placeholder(__('profile_edit.placeholders.tags'))
                                                    ->helperText(__('profile_edit.helpers.tags'))
                                                    ->afterStateHydrated(fn (TagsInput $component, array|string|null $state) => $component->state(
                                                        is_string($state) ? array_map('trim', explode(',', $state)) : ($state ?? []),
                                                    )),
                                            ])
                                            ->columnSpan(1),
                                        FileUpload::make('avatar')
                                            ->label(__('profile_edit.fields.avatar'))
                                            ->required()
                                            ->image()
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditor()
                                            ->maxSize(5120)
                                            ->disk('public')
                                            ->directory('avatars')
                                            ->helperText(__('profile_edit.helpers.avatar'))
                                            ->columnSpan(1),
                                    ]),

                                Section::make(__('profile_edit.sections.address.title'))
                                    ->description(__('profile_edit.sections.address.description'))
                                    ->columns(2)
                                    ->schema([
                                        Select::make('country')
                                            ->label(__('profile_edit.fields.country'))
                                            ->options(Countries::options())
                                            ->searchable()
                                            ->default('Україна'),
                                        TextInput::make('region')
                                            ->label(__('profile_edit.fields.region'))
                                            ->placeholder(__('profile_edit.placeholders.region'))
                                            ->maxLength(255),
                                        TextInput::make('city')
                                            ->label(__('profile_edit.fields.city'))
                                            ->placeholder(__('profile_edit.placeholders.city'))
                                            ->maxLength(255),
                                        TextInput::make('postal_code')
                                            ->label(__('profile_edit.fields.postal_code'))
                                            ->placeholder(__('profile_edit.placeholders.postal_code'))
                                            ->maxLength(20),
                                    ]),

                                Section::make(__('profile_edit.sections.about.title'))
                                    ->description(__('profile_edit.sections.about.description'))
                                    ->schema([
                                        Textarea::make('description')
                                            ->label(__('profile_edit.fields.description'))
                                            ->placeholder(__('profile_edit.placeholders.description'))
                                            ->rows(6)
                                            ->autosize()
                                            ->maxLength(10000)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make(__('profile_edit.tabs.legal'))
                            ->key('legal')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make(__('profile_edit.sections.legal_details.title'))
                                    ->description(__('profile_edit.sections.legal_details.description'))
                                    ->schema([
                                        Toggle::make('profileLegal.is_active')
                                            ->label(__('profile_edit.fields.legal_active'))
                                            ->helperText(__('profile_edit.helpers.legal_active'))
                                            ->live()
                                            ->default(true),
                                    ]),

                                Section::make(__('profile_edit.sections.legal_company.title'))
                                    ->description(__('profile_edit.sections.legal_company.description'))
                                    ->columns(2)
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('profileLegal.name')
                                                    ->label(__('profile_edit.fields.company_name'))
                                                    ->placeholder(__('profile_edit.placeholders.company_name'))
                                                    ->maxLength(255)
                                                    ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                                TextInput::make('profileLegal.edrpou')
                                                    ->label(__('profile_edit.fields.edrpou'))
                                                    ->placeholder(__('profile_edit.placeholders.edrpou'))
                                                    ->helperText(__('profile_edit.helpers.edrpou'))
                                                    ->maxLength(20)
                                                    ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                                TextInput::make('profileLegal.authorized_person')
                                                    ->label(__('profile_edit.fields.authorized_person'))
                                                    ->placeholder(__('profile_edit.placeholders.authorized_person'))
                                                    ->maxLength(255)
                                                    ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                                Select::make('profileLegal.currency')
                                                    ->label(__('profile_edit.fields.currency'))
                                                    ->options([
                                                        Currency::UAH->value => __('profile_edit.currency.uah'),
                                                        Currency::USD->value => __('profile_edit.currency.usd'),
                                                        Currency::EUR->value => __('profile_edit.currency.eur'),
                                                    ])
                                                    ->default(Currency::UAH->value)
                                                    ->required()
                                                    ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                            ])
                                            ->columnSpan(1),
                                        FileUpload::make('profileLegal.logo')
                                            ->label(__('profile_edit.fields.logo'))
                                            ->image()
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditor()
                                            ->maxSize(5120)
                                            ->disk('public')
                                            ->directory('logos')
                                            ->helperText(__('profile_edit.helpers.legal_logo'))
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active'))
                                            ->columnSpan(1),
                                    ]),

                                Section::make(__('profile_edit.sections.legal_contacts.title'))
                                    ->description(__('profile_edit.sections.legal_contacts.description'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('profileLegal.phone')
                                            ->label(__('profile_edit.fields.legal_phone'))
                                            ->tel()
                                            ->placeholder(__('profile_edit.placeholders.phone'))
                                            ->maxLength(50)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        TextInput::make('profileLegal.email')
                                            ->label(__('profile_edit.fields.email'))
                                            ->email()
                                            ->placeholder(__('profile_edit.placeholders.legal_email'))
                                            ->maxLength(255)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        TextInput::make('profileLegal.address')
                                            ->label(__('profile_edit.fields.legal_address'))
                                            ->placeholder(__('profile_edit.placeholders.legal_address'))
                                            ->maxLength(500)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make(__('profile_edit.tabs.social'))
                            ->key('social')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make(__('profile_edit.sections.social_links.title'))
                                    ->description(__('profile_edit.sections.social_links.description'))
                                    ->columns(2)
                                    ->schema($this->socialFields()),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, TextInput>
     */
    private function socialFields(): array
    {
        return collect($this->socialFieldLabels())->map(
            fn (string $label, string $field): TextInput => TextInput::make("profileSocial.{$field}")
                ->label($label)
                ->url()
                ->maxLength(500),
        )->values()->all();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getUser();

        $data['profileLegal'] = $user->profileLegal?->only([
            'is_active',
            'currency',
            'logo',
            'name',
            'edrpou',
            'authorized_person',
            'address',
            'phone',
            'email',
        ]) ?? [
            'is_active' => true,
            'currency' => Currency::UAH->value,
        ];

        if (($data['profileLegal']['currency'] ?? null) instanceof \BackedEnum) {
            $data['profileLegal']['currency'] = $data['profileLegal']['currency']->value;
        }

        $data['profileSocial'] = $user->profileSocial?->only(array_keys($this->socialFieldLabels())) ?? [];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $legalData = Arr::pull($data, 'profileLegal', []);
        $socialData = Arr::pull($data, 'profileSocial', []);

        $record = parent::handleRecordUpdate($record, $data);

        $record->profileLegal()->updateOrCreate([], [
            ...$legalData,
            'currency' => $legalData['currency'] ?? Currency::UAH->value,
            'name' => $legalData['name'] ?? null,
        ]);

        $record->profileSocial()->updateOrCreate([], $socialData);

        return $record;
    }

    /**
     * @return array<string, string>
     */
    private function socialFieldLabels(): array
    {
        return [
            'website' => __('profile_edit.social.website'),
            'facebook' => __('profile_edit.social.facebook'),
            'instagram' => __('profile_edit.social.instagram'),
            'linkedin' => __('profile_edit.social.linkedin'),
            'twitter' => __('profile_edit.social.twitter'),
            'telegram' => __('profile_edit.social.telegram'),
            'youtube' => __('profile_edit.social.youtube'),
            'youtube_channel' => __('profile_edit.social.youtube_channel'),
            'tiktok' => __('profile_edit.social.tiktok'),
            'github' => __('profile_edit.social.github'),
            'pinterest' => __('profile_edit.social.pinterest'),
            'whatsapp' => __('profile_edit.social.whatsapp'),
            'deviantart' => __('profile_edit.social.deviantart'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('profile_edit.saved_notification');
    }
}
