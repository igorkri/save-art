<?php

namespace App\Filament\Profile\Pages\Auth\Concerns;

use App\Enums\Currency;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;

trait HasLegalTab
{
    private function legalTab(): Tab
    {
        return Tab::make(__('profile_edit.tabs.legal'))
            ->key('legal')
            ->icon('heroicon-o-building-office')
            ->schema([
                Tabs::make('legal_sub')
                    ->persistTabInQueryString('legal_tab')
                    ->tabs([
                        $this->legalDetailsTab(),
                        $this->legalCompanyTab(),
                        $this->legalContactsTab(),
                    ]),
            ]);
    }

    private function legalDetailsTab(): Tab
    {
        return Tab::make(__('profile_edit.sections.legal_details.title'))
            ->key('legal_details')
            ->icon('heroicon-o-power')
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
            ]);
    }

    private function legalCompanyTab(): Tab
    {
        return Tab::make(__('profile_edit.sections.legal_company.title'))
            ->key('legal_company')
            ->icon('heroicon-o-building-office-2')
            ->schema([
                Section::make(__('profile_edit.sections.legal_company.title'))
                    ->description(__('profile_edit.sections.legal_company.description'))
                    ->schema([
                        Grid::make(6)
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
                                    ->columnSpan(4),
                                FileUpload::make('profileLegal.logo')
                                    ->label(__('profile_edit.fields.logo'))
                                    ->image()
                                    ->avatar()
                                    ->imageCropAspectRatio('1:1')
                                    ->imageEditor()
                                    ->maxSize(5120)
                                    ->disk('public')
                                    ->directory('logos')
                                    ->belowContent(
                                        Text::make(__('profile_edit.helpers.legal_logo'))
                                            ->extraAttributes(['class' => '!flex !w-full justify-center text-center'])
                                    )
                                    ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active'))
                                    ->alignCenter()
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }

    private function legalContactsTab(): Tab
    {
        return Tab::make(__('profile_edit.sections.legal_contacts.title'))
            ->key('legal_contacts')
            ->icon('heroicon-o-phone')
            ->schema([
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
            ]);
    }
}
