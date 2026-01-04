<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendMessage')
                ->label('Написати повідомлення')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->form([
                    TextInput::make('subject')
                        ->label('Тема')
                        ->maxLength(255),
                    Textarea::make('content')
                        ->label('Повідомлення')
                        ->required()
                        ->rows(5)
                        ->placeholder('Введіть текст повідомлення...'),
                ])
                ->action(function (array $data): void {
                    Message::create([
                        'user_id' => $this->record->id,
                        'admin_id' => Auth::id(),
                        'subject' => $data['subject'],
                        'content' => $data['content'],
                        'direction' => 'admin_to_user',
                    ]);

                    Notification::make()
                        ->title('Повідомлення надіслано')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    // title для сторінки редагування
    public function getTitle(): string
    {
        $name = $this->record->name ?? 'Редагування користувача';

        return "Редагування користувача: {$name}";
    }

    protected function afterSaveRecord(): void
    {
        Log::info('afterSaveRecord called');

        parent::afterSaveRecord();
        $user = $this->record;
        $data = $this->form->getState();

        // ProfilePersonal
        $personalData = $data['profilePersonal'] ?? [];
        $personal = $user->profilePersonal;
        if (! $personal) {
            $personal = new \App\Models\ProfilePersonal;
            $personal->user_id = $user->id;
        }

        // Заполняем обычные поля
        foreach ($personalData as $key => $value) {
            if (! in_array($key, ['full_name', 'profession', 'tags', 'country', 'region', 'city', 'description'])) {
                $personal->$key = $value;
            }
        }

        // Для полей с array cast используем setAttribute, который автоматически применит cast
        foreach (['full_name', 'profession', 'tags', 'country', 'region', 'city', 'description'] as $field) {
            if (isset($personalData[$field])) {
                $personal->setAttribute($field, $personalData[$field]);
            }
        }

        $personal->save();

        // ProfileLegal
        $legalData = $data['profileLegal'] ?? [];
        $legal = $user->profileLegal;
        if (! $legal) {
            $legal = new \App\Models\ProfileLegal;
            $legal->user_id = $user->id;
        }

        // Заполняем обычные поля
        foreach ($legalData as $key => $value) {
            if (! in_array($key, ['name', 'authorized_person', 'address'])) {
                $legal->$key = $value;
            }
        }

        // Встановлюємо значення за замовчуванням для currency, якщо воно null
        if (empty($legal->currency)) {
            $legal->currency = 'UAH';
        }

        // Для полей с array cast используем setAttribute, который автоматически применит cast
        foreach (['name', 'authorized_person', 'address'] as $field) {
            if (isset($legalData[$field])) {
                $legal->setAttribute($field, $legalData[$field]);
            }
        }

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
        if (! empty($personalData)) {
            $personal = $record->profilePersonal;
            if (! $personal) {
                $personal = new \App\Models\ProfilePersonal;
                $personal->user_id = $record->id;
            }

            // Заполняем обычные поля
            foreach ($personalData as $key => $value) {
                if (! in_array($key, ['full_name', 'profession', 'tags', 'country', 'region', 'city', 'description'])) {
                    $personal->$key = $value;
                }
            }

            // Для полей с array cast используем setAttribute, который автоматически применит cast
            foreach (['full_name', 'profession', 'tags', 'country', 'region', 'city', 'description'] as $field) {
                if (isset($personalData[$field])) {
                    $personal->setAttribute($field, $personalData[$field]);
                }
            }

            $personal->save();
        }

        // ProfileLegal
        $legalData = $data['profileLegal'] ?? [];
        if (! empty($legalData)) {
            $legal = $record->profileLegal;
            if (! $legal) {
                $legal = new \App\Models\ProfileLegal;
                $legal->user_id = $record->id;
            }

            // Заполняем обычные поля
            foreach ($legalData as $key => $value) {
                if (! in_array($key, ['name', 'authorized_person', 'address'])) {
                    $legal->$key = $value;
                }
            }

            // Встановлюємо значення за замовчуванням для currency, якщо воно null
            if (empty($legal->currency)) {
                $legal->currency = 'UAH';
            }

            // Для полей с array cast используем setAttribute, который автоматически применит cast
            foreach (['name', 'authorized_person', 'address'] as $field) {
                if (isset($legalData[$field])) {
                    $legal->setAttribute($field, $legalData[$field]);
                }
            }

            $legal->save();
        }

        // ProfileSocial
        $socialData = $data['profileSocial'] ?? [];
        if (! empty($socialData)) {
            $social = $record->profileSocial ?: new \App\Models\ProfileSocial(['user_id' => $record->id]);
            $social->fill($socialData);
            $social->save();
        }

        // Смена пароля
        if (! empty($data['password_new'])) {
            $record->password = bcrypt($data['password_new']);
            $record->save();
        }

        // ProfileDocuments - обработка загруженных файлов
        if (isset($data['profileDocuments'])) {
            $newDocuments = $data['profileDocuments'] ?? [];

            // Получаем существующие пути к файлам
            $existingDocuments = $record->profileDocuments->pluck('file_path')->toArray();

            // Удаляем документы, которые были удалены из формы
            $documentsToDelete = array_diff($existingDocuments, $newDocuments);
            if (! empty($documentsToDelete)) {
                $record->profileDocuments()
                    ->whereIn('file_path', $documentsToDelete)
                    ->each(function ($doc) {
                        // Удаляем файл с диска
                        Storage::disk('public')->delete($doc->file_path);
                        $doc->delete();
                    });
            }

            // Добавляем новые документы
            $documentsToAdd = array_diff($newDocuments, $existingDocuments);
            foreach ($documentsToAdd as $filePath) {
                if (! empty($filePath)) {
                    $fullPath = storage_path('app/public/'.$filePath);
                    if (file_exists($fullPath)) {
                        $fileHash = hash_file('sha256', $fullPath);

                        // Проверяем, существует ли уже документ с таким хешем для этого пользователя
                        $existingDocument = $record->profileDocuments()
                            ->where('hash', $fileHash)
                            ->first();

                        if (! $existingDocument) {
                            try {
                                // Создаём новый документ только если его ещё нет
                                $document = new \App\Models\ProfileDocument([
                                    'user_id' => $record->id,
                                    'file_path' => $filePath,
                                    'hash' => $fileHash,
                                    'sign_status' => 'pending',
                                    'service' => 'diia',
                                ]);
                                $document->save();
                            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                                // Документ с таким хешем уже существует, пропускаем
                                Log::warning('Документ з таким хешем вже існує', [
                                    'user_id' => $record->id,
                                    'file_path' => $filePath,
                                    'hash' => $fileHash,
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Помилка при збереженні документа', [
                                    'user_id' => $record->id,
                                    'file_path' => $filePath,
                                    'error' => $e->getMessage(),
                                ]);
                                throw $e;
                            }
                        }
                    }
                }
            }
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

            // Загружаем пути к документам
            $documents = method_exists($record, 'profileDocuments') && $record->profileDocuments
                ? $record->profileDocuments->pluck('file_path')->toArray()
                : [];

            $this->form->fill([
                'name' => $record->name ?? '',
                'email' => $record->email ?? '',
                'role' => $record->role instanceof \BackedEnum ? $record->role->value : $record->role,
                'email_verified_at' => $record->email_verified_at ?? null,
                'profilePersonal' => $personal,
                'profileLegal' => $legal,
                'profileSocial' => $social,
                'profileDocuments' => $documents,
            ]);
        }
    }
}
