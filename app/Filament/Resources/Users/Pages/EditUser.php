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
use Illuminate\Validation\ValidationException;

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

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title('Помилка валідації')
            ->body(collect($exception->errors())->flatten()->first())
            ->danger()
            ->send();
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

        // Встановлюємо значення за замовчуванням для name, якщо воно null або порожнє
        if (empty($legal->name)) {
            $legal->name = ['uk' => '', 'en' => ''];
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
        // Якщо $record — строка (id), завантажуємо модель вручну
        if (is_string($record) || is_int($record)) {
            $record = \App\Models\User::find($record);
        }
        if ($record && is_object($record)) {
            $legal = method_exists($record, 'profileLegal') && $record->profileLegal ? [
                'is_active' => $record->profileLegal->is_active,
                'currency' => $record->profileLegal->currency instanceof \BackedEnum ? $record->profileLegal->currency->value : $record->profileLegal->currency,
                'logo' => $record->profileLegal->logo,
                'name' => $record->profileLegal->name,
                'edrpou' => $record->profileLegal->edrpou,
                'authorized_person' => $record->profileLegal->authorized_person,
                'address' => $record->profileLegal->address,
                'phone' => $record->profileLegal->phone,
                'email' => $record->profileLegal->email,
            ] : [];
            $social = method_exists($record, 'profileSocial') && $record->profileSocial ? collect($record->profileSocial->toArray())->only([
                'website', 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'pinterest', 'github', 'telegram', 'tiktok', 'youtube_channel', 'whatsapp', 'deviantart',
            ])->toArray() : [];

            // Завантажуємо шляхи до документів
            $documents = method_exists($record, 'profileDocuments') && $record->profileDocuments
                ? $record->profileDocuments->pluck('file_path')->toArray()
                : [];

            $this->form->fill([
                // Основні дані користувача
                'email' => $record->email ?? '',
                'role' => $record->role instanceof \BackedEnum ? $record->role->value : $record->role,
                'email_verified_at' => $record->email_verified_at ?? null,
                'is_blocked' => $record->is_blocked ?? false,
                'blocked_until' => $record->blocked_until ?? null,

                // Персональні дані (тепер в User)
                'avatar' => $record->avatar,
                'full_name' => $record->full_name ?? ['uk' => '', 'en' => ''],
                'profession' => $record->profession ?? ['uk' => '', 'en' => ''],
                'tags' => $record->tags ?? ['uk' => '', 'en' => ''],
                'country' => $record->country ?? ['uk' => '', 'en' => ''],
                'region' => $record->region ?? ['uk' => '', 'en' => ''],
                'city' => $record->city ?? ['uk' => '', 'en' => ''],
                'postal_code' => $record->postal_code,
                'phone' => $record->phone,
                'profile_type' => $record->profile_type instanceof \BackedEnum ? $record->profile_type->value : $record->profile_type,
                'description' => $record->description ?? ['uk' => '', 'en' => ''],

                // Профілі
                'profileLegal' => $legal,
                'profileSocial' => $social,
                'profileDocuments' => $documents,
            ]);
        }
    }
}
