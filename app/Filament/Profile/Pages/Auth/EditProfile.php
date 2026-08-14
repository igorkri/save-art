<?php

namespace App\Filament\Profile\Pages\Auth;

use App\Enums\Currency;
use App\Enums\ProfileType;
use App\Enums\SignService;
use App\Models\ProfileDocument;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Мій профіль';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(false)
            ->components([
                Tabs::make('profile')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Персональний профіль')
                            ->key('personal')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Облікові дані')
                                    ->description('Email та пароль, які використовуються для входу в кабінет.')
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
                                        TextInput::make('phone')
                                            ->label('Телефон')
                                            ->required()
                                            ->tel()
                                            ->telRegex('/^\+[1-9]\d{6,14}$/')
                                            ->placeholder('+380 XX XXX XX XX')
                                            ->helperText('У міжнародному форматі, починаючи з +.')
                                            ->maxLength(50),
                                    ]),

                                Section::make('Аватар та ім\'я')
                                    ->description('Ці дані відображаються на вашій публічній сторінці.')
                                    ->columns(2)
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('full_name')
                                                    ->label('ПІБ')
                                                    ->required()
                                                    ->placeholder('Наприклад: Олена Коваленко')
                                                    ->maxLength(255),
                                                TextInput::make('profession')
                                                    ->label('Професія')
                                                    ->placeholder('Наприклад: Художниця, ілюстраторка')
                                                    ->maxLength(255),
                                                TagsInput::make('tags')
                                                    ->label('Теги')
                                                    ->placeholder('живопис, ілюстрація, акварель')
                                                    ->helperText('Вони допоможуть меценатам знайти вас.')
                                                    ->afterStateHydrated(fn (TagsInput $component, array|string|null $state) => $component->state(
                                                        is_string($state) ? array_map('trim', explode(',', $state)) : ($state ?? []),
                                                    )),
                                            ])
                                            ->columnSpan(1),
                                        FileUpload::make('avatar')
                                            ->label('Аватар')
                                            ->required()
                                            ->image()
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditor()
                                            ->maxSize(5120)
                                            ->disk('public')
                                            ->directory('avatars')
                                            ->helperText('Квадратне зображення, до 5 МБ.')
                                            ->columnSpan(1),
                                    ]),

                                Section::make('Адреса доставки')
                                    ->description('Використовується для доставки нагород меценатам.')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('country')
                                            ->label('Країна')
                                            ->options(Countries::options())
                                            ->searchable()
                                            ->default('Україна'),
                                        TextInput::make('region')
                                            ->label('Область / регіон')
                                            ->placeholder('Київська область')
                                            ->maxLength(255),
                                        TextInput::make('city')
                                            ->label('Місто')
                                            ->placeholder('Київ')
                                            ->maxLength(255),
                                        TextInput::make('postal_code')
                                            ->label('Поштовий індекс')
                                            ->placeholder('01001')
                                            ->maxLength(20),
                                    ]),

                                Section::make('Про себе')
                                    ->description('Розкажіть про себе — цей текст побачать відвідувачі вашої публічної сторінки.')
                                    ->schema([
                                        Textarea::make('description')
                                            ->label('Опис / біографія')
                                            ->placeholder('Розкажіть про свій творчий шлях, стиль та джерела натхнення...')
                                            ->rows(6)
                                            ->maxLength(10000)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Юридичний профіль')
                            ->key('legal')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Реквізити')
                                    ->description('Заповніть, якщо отримуєте платежі як юридична особа або ФОП. Вимкніть перемикач нижче, якщо це наразі не потрібно.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('profileLegal.is_active')
                                            ->label('Активний юридичний профіль')
                                            ->helperText('Коли вимкнено, реквізити нижче не використовуються під час виплат.')
                                            ->live()
                                            ->default(true)
                                            ->columnSpanFull(),
                                        Select::make('profileLegal.currency')
                                            ->label('Основна валюта')
                                            ->options([
                                                Currency::UAH->value => 'Гривня (UAH)',
                                                Currency::USD->value => 'Долар (USD)',
                                                Currency::EUR->value => 'Євро (EUR)',
                                            ])
                                            ->default(Currency::UAH->value)
                                            ->required()
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        FileUpload::make('profileLegal.logo')
                                            ->label('Логотип')
                                            ->image()
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditor()
                                            ->maxSize(5120)
                                            ->disk('public')
                                            ->directory('logos')
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active'))
                                            ->columnSpanFull(),
                                        TextInput::make('profileLegal.name')
                                            ->label('Назва компанії')
                                            ->maxLength(255)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        TextInput::make('profileLegal.edrpou')
                                            ->label('ЄДРПОУ')
                                            ->maxLength(20)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        TextInput::make('profileLegal.authorized_person')
                                            ->label('Уповноважена особа')
                                            ->maxLength(255)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        TextInput::make('profileLegal.phone')
                                            ->label('Телефон')
                                            ->tel()
                                            ->maxLength(50)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        TextInput::make('profileLegal.email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active')),
                                        TextInput::make('profileLegal.address')
                                            ->label('Адреса')
                                            ->maxLength(500)
                                            ->disabled(fn (Get $get): bool => ! $get('profileLegal.is_active'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Соціальні мережі')
                            ->key('social')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make('Посилання')
                                    ->description('Додайте посилання на ваші профілі та сайт. Усі поля необов\'язкові — заповніть лише ті, що маєте.')
                                    ->columns(2)
                                    ->schema($this->socialFields()),
                            ]),

                        Tab::make('Документи')
                            ->key('documents')
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
