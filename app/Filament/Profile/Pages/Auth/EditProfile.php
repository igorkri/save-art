<?php

namespace App\Filament\Profile\Pages\Auth;

use App\Enums\Currency;
use App\Enums\ProfileType;
use App\Enums\SignService;
use App\Models\ProfileDocument;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Мій профіль';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('profile')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Персональний профіль')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Облікові дані')
                                    ->columns(2)
                                    ->schema([
                                        $this->getEmailFormComponent(),
                                        Select::make('profile_type')
                                            ->label('Тип профілю')
                                            ->options(ProfileType::getOptions())
                                            ->disabled()
                                            ->dehydrated(false),
                                        $this->getPasswordFormComponent(),
                                        $this->getPasswordConfirmationFormComponent(),
                                        $this->getCurrentPasswordFormComponent(),
                                    ]),

                                Section::make('Основна інформація')
                                    ->columns(2)
                                    ->schema([
                                        FileUpload::make('avatar')
                                            ->label('Аватар')
                                            ->image()
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditor()
                                            ->maxSize(5120)
                                            ->disk('public')
                                            ->directory('avatars')
                                            ->columnSpanFull(),
                                        TextInput::make('full_name')
                                            ->label('ПІБ')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('profession')
                                            ->label('Професія')
                                            ->maxLength(255),
                                        TextInput::make('tags')
                                            ->label('Теги')
                                            ->maxLength(255),
                                        TextInput::make('phone')
                                            ->label('Телефон')
                                            ->tel()
                                            ->maxLength(50),
                                        TextInput::make('country')
                                            ->label('Країна')
                                            ->maxLength(255),
                                        TextInput::make('region')
                                            ->label('Область / регіон')
                                            ->maxLength(255),
                                        TextInput::make('city')
                                            ->label('Місто')
                                            ->maxLength(255),
                                        TextInput::make('postal_code')
                                            ->label('Поштовий індекс')
                                            ->maxLength(20),
                                        Textarea::make('description')
                                            ->label('Опис / біографія')
                                            ->rows(6)
                                            ->maxLength(10000)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Юридичний профіль')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Реквізити')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('profileLegal.is_active')
                                            ->label('Активний профіль')
                                            ->default(true),
                                        Select::make('profileLegal.currency')
                                            ->label('Основна валюта')
                                            ->options([
                                                Currency::UAH->value => 'Гривня (UAH)',
                                                Currency::USD->value => 'Долар (USD)',
                                                Currency::EUR->value => 'Євро (EUR)',
                                            ])
                                            ->default(Currency::UAH->value)
                                            ->required(),
                                        FileUpload::make('profileLegal.logo')
                                            ->label('Логотип')
                                            ->image()
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditor()
                                            ->maxSize(5120)
                                            ->disk('public')
                                            ->directory('logos')
                                            ->columnSpanFull(),
                                        TextInput::make('profileLegal.name')
                                            ->label('Назва компанії')
                                            ->maxLength(255),
                                        TextInput::make('profileLegal.edrpou')
                                            ->label('ЄДРПОУ')
                                            ->maxLength(20),
                                        TextInput::make('profileLegal.authorized_person')
                                            ->label('Уповноважена особа')
                                            ->maxLength(255),
                                        TextInput::make('profileLegal.phone')
                                            ->label('Телефон')
                                            ->tel()
                                            ->maxLength(50),
                                        TextInput::make('profileLegal.email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('profileLegal.address')
                                            ->label('Адреса')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Соціальні мережі')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make('Посилання')
                                    ->columns(2)
                                    ->schema($this->socialFields()),
                            ]),

                        Tab::make('Документи')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Документи профілю')
                                    ->description('Завантажені документи зберігаються у вашому профілі та можуть використовуватися для електронного підпису.')
                                    ->schema([
                                        FileUpload::make('profileDocuments')
                                            ->label('Файли')
                                            ->multiple()
                                            ->downloadable()
                                            ->openable()
                                            ->maxSize(15360)
                                            ->disk('public')
                                            ->directory('profile_documents')
                                            ->columnSpanFull(),
                                    ]),
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
        $data['profileDocuments'] = $user->profileDocuments()->pluck('file_path')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $legalData = Arr::pull($data, 'profileLegal', []);
        $socialData = Arr::pull($data, 'profileSocial', []);
        $documentPaths = Arr::pull($data, 'profileDocuments', []);

        $record = parent::handleRecordUpdate($record, $data);

        $record->profileLegal()->updateOrCreate([], [
            ...$legalData,
            'currency' => $legalData['currency'] ?? Currency::UAH->value,
            'name' => $legalData['name'] ?? null,
        ]);

        $record->profileSocial()->updateOrCreate([], $socialData);
        $this->syncProfileDocuments($record, is_array($documentPaths) ? $documentPaths : []);

        return $record;
    }

    /**
     * @param  list<string>  $documentPaths
     */
    private function syncProfileDocuments(User $user, array $documentPaths): void
    {
        $documentPaths = array_values(array_unique(array_filter($documentPaths)));
        $existingDocuments = $user->profileDocuments()->get()->keyBy('file_path');
        $pathsToDelete = $existingDocuments->keys()->diff($documentPaths);
        $pathsToAdd = collect($documentPaths)->diff($existingDocuments->keys());

        foreach ($pathsToAdd as $filePath) {
            if (! Storage::disk('public')->exists($filePath)) {
                continue;
            }

            $hash = hash_file('sha256', Storage::disk('public')->path($filePath));

            if ($hash === false) {
                throw ValidationException::withMessages([
                    'data.profileDocuments' => 'Не вдалося прочитати документ.',
                ]);
            }
            $duplicate = ProfileDocument::query()->where('hash', $hash)->first();

            if ($duplicate !== null) {
                Storage::disk('public')->delete($filePath);

                throw ValidationException::withMessages([
                    'data.profileDocuments' => 'Такий документ уже завантажено.',
                ]);
            }

            $user->profileDocuments()->create([
                'file_path' => $filePath,
                'hash' => $hash,
                'sign_status' => 'pending',
                'service' => SignService::Diia->value,
            ]);
        }

        if ($pathsToDelete->isEmpty()) {
            return;
        }

        $user->profileDocuments()->whereIn('file_path', $pathsToDelete)->delete();

        DB::afterCommit(fn () => Storage::disk('public')->delete($pathsToDelete->all()));
    }

    /**
     * @return array<string, string>
     */
    private function socialFieldLabels(): array
    {
        return [
            'website' => 'Вебсайт',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'twitter' => 'X / Twitter',
            'telegram' => 'Telegram',
            'youtube' => 'YouTube',
            'youtube_channel' => 'YouTube-канал',
            'tiktok' => 'TikTok',
            'github' => 'GitHub',
            'pinterest' => 'Pinterest',
            'whatsapp' => 'WhatsApp',
            'deviantart' => 'DeviantArt',
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Профіль збережено';
    }
}
