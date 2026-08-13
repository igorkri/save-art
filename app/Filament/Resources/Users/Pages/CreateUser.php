<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Створити користувача';

    protected function afterCreate(): void
    {
        $user = $this->record;
        $data = $this->form->getState();

        // ProfileLegal
        $legalData = $data['profileLegal'] ?? [];
        $legal = new \App\Models\ProfileLegal;
        $legal->user_id = $user->id;

        $legal->fill($legalData);

        // Встановлюємо значення за замовчуванням для currency, якщо воно null
        if (empty($legal->currency)) {
            $legal->currency = 'UAH';
        }

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

                    // Перевіряємо, чи існує документ з таким хешем
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
                            Log::warning('Документ з таким хешем вже існує при створенні користувача', [
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
