<?php

namespace App\Filament\Profile\Pages\Auth;

use App\Enums\Currency;
use App\Filament\Profile\Pages\Auth\Concerns\HasLegalTab;
use App\Filament\Profile\Pages\Auth\Concerns\HasPersonalTab;
use App\Filament\Profile\Pages\Auth\Concerns\HasSocialTab;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditProfile extends BaseEditProfile
{
    use HasLegalTab;
    use HasPersonalTab;
    use HasSocialTab;

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
                    ->extraAttributes(['class' => 'profile-edit-tabs'])
                    ->tabs([
                        $this->personalTab(),
                        $this->legalTab(),
                        $this->socialTab(),
                    ])
                    ->columnSpanFull(),
            ]);
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

        // Перше збереження обов'язкових полів (full_name/avatar/phone — усі
        // required() у HasPersonalTab) фіксується як "профіль заповнено":
        // саме на profile_completed_at орієнтуються фронтенди, показуючи
        // повне меню кабінету, а не на сам факт наявності profile_type
        // (який тепер проставляється дефолтом одразу при реєстрації).
        if ($record->profile_completed_at === null) {
            $record->forceFill(['profile_completed_at' => now()])->save();
        }

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('profile_edit.saved_notification');
    }
}
