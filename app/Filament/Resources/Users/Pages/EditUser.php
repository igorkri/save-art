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
}
