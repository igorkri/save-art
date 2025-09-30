<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSaveRecord(): void
    {
        Log::info('afterSaveRecord called');

        parent::afterSaveRecord();
        $user = $this->record;
        $data = $this->form->getState();

        // ProfilePersonal
        $personalData = $data['profilePersonal'] ?? [];
        $personal = $user->profilePersonal ?: new \App\Models\ProfilePersonal(['user_id' => $user->id]);
        $personal->fill($personalData);
        $personal->save();

        // ProfileLegal
        $legalData = $data['profileLegal'] ?? [];
        $legal = $user->profileLegal ?: new \App\Models\ProfileLegal(['user_id' => $user->id]);
        $legal->fill($legalData);
        $legal->save();

        // ProfileSocial
        $socialData = $data['profileSocial'] ?? [];
        $social = $user->profileSocial ?: new \App\Models\ProfileSocial(['user_id' => $user->id]);
        $social->fill($socialData);
        $social->save();
    }

    protected function handleRecordUpdate(
        \Illuminate\Database\Eloquent\Model $record,
        array $data
    ): \Illuminate\Database\Eloquent\Model {

        Log::info('handleRecordUpdate called', ['data' => $data, 'record' => $record->toArray()]);

        $updatedRecord = parent::handleRecordUpdate($record, $data);

        // ProfilePersonal
        $personalData = $data['profilePersonal'] ?? [];
        if (!empty($personalData)) {
            $personal = $record->profilePersonal ?: new \App\Models\ProfilePersonal(['user_id' => $record->id]);
            $personal->fill($personalData);
            $personal->save();
        }

        // ProfileLegal
        $legalData = $data['profileLegal'] ?? [];
        if (!empty($legalData)) {
            $legal = $record->profileLegal ?: new \App\Models\ProfileLegal(['user_id' => $record->id]);
            $legal->fill($legalData);
            $legal->save();
        }

        // ProfileSocial
        $socialData = $data['profileSocial'] ?? [];
        if (!empty($socialData)) {
            $social = $record->profileSocial ?: new \App\Models\ProfileSocial(['user_id' => $record->id]);
            $social->fill($socialData);
            $social->save();
        }

        return $updatedRecord;
    }

    public function mount($record = null): void
    {
        parent::mount($record);
        // Если $record — строка (id), загружаем модель вручную
        if (is_string($record) || is_int($record)) {
            $record = \App\Models\User::find($record);
        }
        if ($record && is_object($record)) {
            $personal = method_exists($record, 'profilePersonal') && $record->profilePersonal ? collect($record->profilePersonal->toArray())->only([
                'avatar', 'full_name', 'profession', 'tags', 'country', 'region', 'city', 'postal_code', 'role', 'description',
            ])->toArray() : [];
            $legal = method_exists($record, 'profileLegal') && $record->profileLegal ? collect($record->profileLegal->toArray())->only([
                'currency', 'is_legal', 'logo', 'name', 'edrpou', 'authorized_person', 'address', 'phone', 'email',
            ])->toArray() : [];
            $social = method_exists($record, 'profileSocial') && $record->profileSocial ? collect($record->profileSocial->toArray())->only([
                'website', 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'pinterest', 'github', 'telegram', 'tiktok', 'youtube_channel', 'whatsapp', 'deviantart',
            ])->toArray() : [];
            $this->form->fill([
                'name' => $record->name ?? '',
                'email' => $record->email ?? '',
                'role' => $record->role instanceof \BackedEnum ? $record->role->value : $record->role,
                'email_verified_at' => $record->email_verified_at ?? null,
                'profilePersonal' => $personal,
                'profileLegal' => $legal,
                'profileSocial' => $social,
            ]);
        }
    }
}
