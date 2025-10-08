<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        parent::afterCreate();
        $user = $this->record;
        $data = $this->form->getState();

        // ProfilePersonal
        $personalData = $data['profilePersonal'] ?? [];
        $personal = new \App\Models\ProfilePersonal(['user_id' => $user->id]);
        $personal->fill($personalData);
        $personal->save();

        // ProfileLegal
        $legalData = $data['profileLegal'] ?? [];
        $legal = new \App\Models\ProfileLegal(['user_id' => $user->id]);
        $legal->fill($legalData);
        $legal->save();

        // ProfileSocial
        $socialData = $data['profileSocial'] ?? [];
        $social = new \App\Models\ProfileSocial(['user_id' => $user->id]);
        $social->fill($socialData);
        $social->save();
    }
}
