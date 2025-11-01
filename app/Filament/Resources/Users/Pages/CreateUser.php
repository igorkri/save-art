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

        // ProfileDocuments
        $documentsData = $data['profileDocuments'] ?? [];
        if (! empty($documentsData) && is_array($documentsData)) {
            foreach ($documentsData as $filePath) {
                $fullPath = storage_path('app/public/'.$filePath);
                if (file_exists($fullPath)) {
                    $fileHash = hash_file('sha256', $fullPath);

                    // Проверяем, существует ли уже документ с таким хешем
                    $existingDocument = \App\Models\ProfileDocument::where('hash', $fileHash)
                        ->where('user_id', $user->id)
                        ->first();

                    if (! $existingDocument) {
                        try {
                            $document = new \App\Models\ProfileDocument([
                                'user_id' => $user->id,
                                'file_path' => $filePath,
                                'hash' => $fileHash,
                                'sign_status' => 'pending',
                                'service' => 'diia',
                            ]);
                            $document->save();
                        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                            // Документ с таким хешем уже существует, пропускаем
                            \Log::warning('Документ з таким хешем вже існує при створенні користувача', [
                                'user_id' => $user->id,
                                'file_path' => $filePath,
                                'hash' => $fileHash,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
