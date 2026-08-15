<?php

namespace App\Filament\Profile\Pages\Auth;

use App\Enums\ProfileType;
use App\Notifications\VerifyEmailNotification;
use App\Support\FrontendUrlResolver;
use App\UserRole;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Register extends BaseRegister
{
    protected function getNameFormComponent(): Component
    {
        return TextInput::make('full_name')
            ->label(__('filament-panels::auth/pages/register.form.name.label'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data['slug'] = Str::slug($data['full_name']).'-'.Str::random(6);
        $data['role'] = UserRole::User;
        $data['profile_type'] = ProfileType::Artist;

        return $data;
    }

    /**
     * Панель профілю не використовує вбудовану Filament-верифікацію email
     * (немає ->emailVerification() у ProfilePanelProvider), тому надсилаємо
     * той самий лист підтвердження, що й API-реєстрація, з посиланням на
     * фронтенд save-art — інакше Filament::getVerifyEmailUrl() впаде на
     * відсутньому роуті filament.profile.auth.email-verification.verify.
     */
    protected function sendEmailVerificationNotification(Model $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->notify(new VerifyEmailNotification(FrontendUrlResolver::resolve(request())));
    }
}
