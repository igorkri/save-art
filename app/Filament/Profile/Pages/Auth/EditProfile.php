<?php

namespace App\Filament\Profile\Pages\Auth;

use App\Enums\Currency;
use App\Enums\SignService;
use App\Filament\Profile\Pages\Auth\Concerns\HasAgreementTab;
use App\Filament\Profile\Pages\Auth\Concerns\HasLegalTab;
use App\Filament\Profile\Pages\Auth\Concerns\HasPersonalTab;
use App\Filament\Profile\Pages\Auth\Concerns\HasSecurityTab;
use App\Filament\Profile\Pages\Auth\Concerns\HasSocialTab;
use App\Models\ProfileDocument;
use App\Models\User;
use App\Services\ContractService;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditProfile extends BaseEditProfile
{
    use HasAgreementTab;
    use HasLegalTab;
    use HasPersonalTab;
    use HasSecurityTab;
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
                Text::make(__('profile_edit.completion_prompt'))
                    ->extraAttributes(['class' => 'profile-edit-intro'])
                    ->visible(fn (): bool => ! $this->getUser()->isProfileComplete()),
                Tabs::make('profile')
                    ->persistTabInQueryString()
                    ->extraAttributes(['class' => 'profile-edit-tabs'])
                    ->tabs([
                        $this->legalTab(),
                        $this->personalTab(),
                        $this->socialTab(),
                        $this->securityTab(),
                        $this->agreementTab(),
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

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(__('profile_edit.actions.save'))
                ->icon('heroicon-o-check'),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Center;
    }

    public function prepareContract(): void
    {
        /** @var User $user */
        $user = $this->getUser();
        app(ContractService::class)->getOrCreatePendingContract($user);

        Notification::make()
            ->success()
            ->title(__('profile_edit.messages.contract_prepared'))
            ->body(__('profile_edit.messages.contract_prepared_body'))
            ->send();
    }

    public function requestProfileDeletion(): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->forceFill(['deletion_requested_at' => now()])->save();

        Notification::make()
            ->warning()
            ->title(__('profile_edit.messages.deletion_requested'))
            ->send();
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
                    'data.profileDocuments' => __('profile_edit.messages.document_unreadable'),
                ]);
            }

            if (ProfileDocument::query()->where('hash', $hash)->exists()) {
                Storage::disk('public')->delete($filePath);

                throw ValidationException::withMessages([
                    'data.profileDocuments' => __('profile_edit.messages.document_duplicate'),
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

    protected function getSavedNotificationTitle(): ?string
    {
        return __('profile_edit.saved_notification');
    }
}
