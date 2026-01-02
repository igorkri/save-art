<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileLegalRequest;
use App\Http\Requests\UpdateProfilePersonalRequest;
use App\Http\Requests\UpdateProfileSocialRequest;
use App\Http\Requests\UploadProfileDocumentRequest;
use App\Http\Resources\ProfileDocumentResource;
use App\Http\Resources\ProfileLegalResource;
use App\Http\Resources\ProfilePersonalResource;
use App\Models\ProfileDocument;
use App\Models\ProfileLegal;
use App\Models\ProfilePersonal;
use App\Models\ProfileSocial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileApiController extends Controller
{
    /**
     * Отримати профіль поточного користувача
     */
    public function getProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['profilePersonal', 'profileLegal', 'profileSocial', 'profileDocuments']);

        return response()->json([
            'user' => $user,
            'profilePersonal' => $user->profilePersonal ? new ProfilePersonalResource($user->profilePersonal) : null,
            'profileLegal' => $user->profileLegal ? new ProfileLegalResource($user->profileLegal) : null,
            'profileSocial' => $user->profileSocial,
            'profileDocuments' => ProfileDocumentResource::collection($user->profileDocuments),
        ]);
    }

    /**
     * Оновити особистий профіль користувача
     */
    public function updatePersonal(UpdateProfilePersonalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $profile = $user->profilePersonal;
        if (! $profile) {
            $profile = new ProfilePersonal(['user_id' => $user->id]);
        }
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profilePersonal' => new ProfilePersonalResource($profile)]);
    }

    /**
     * Створити особистий профіль користувача
     */
    public function createPersonal(UpdateProfilePersonalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user->profilePersonal()->exists()) {
            return response()->json(['message' => __('api.profile.personal_exists')], 409);
        }
        $profile = new ProfilePersonal(['user_id' => $user->id]);
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profilePersonal' => new ProfilePersonalResource($profile)], 201);
    }

    /**
     * Оновити юридичний профіль користувача
     */
    public function updateLegal(UpdateProfileLegalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $profile = $user->profileLegal;
        if (! $profile) {
            $profile = new ProfileLegal(['user_id' => $user->id]);
        }
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profileLegal' => new ProfileLegalResource($profile)]);
    }

    /**
     * Створити юридичний профіль користувача
     */
    public function createLegal(UpdateProfileLegalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user->profileLegal()->exists()) {
            return response()->json(['message' => __('api.profile.legal_exists')], 409);
        }

        $profile = new ProfileLegal(['user_id' => $user->id]);
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profileLegal' => new ProfileLegalResource($profile)], 201);
    }

    /**
     * Оновити соціальний профіль користувача
     */
    public function updateSocial(UpdateProfileSocialRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $profile = $user->profileSocial;
        if (! $profile) {
            $profile = new ProfileSocial(['user_id' => $user->id]);
        }
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profileSocial' => $profile]);
    }

    /**
     * Створити соціальний профіль користувача
     */
    public function createSocial(UpdateProfileSocialRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (ProfileSocial::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => __('api.profile.social_exists')], 409);
        }
        $profile = new ProfileSocial(['user_id' => $user->id]);
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profileSocial' => $profile], 201);
    }

    /**
     * Отримати всі документи користувача
     */
    public function getDocuments(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $documents = $user->profileDocuments;

        return response()->json([
            'documents' => ProfileDocumentResource::collection($documents),
        ]);
    }

    /**
     * Завантажити новий документ
     */
    public function uploadDocument(UploadProfileDocumentRequest $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $file = $request->file('file');

        // Вычисляем хеш ДО сохранения файла (для работы с fake storage)
        $fileHash = hash('sha256', $file->get());

        // Проверяем существование документа с таким хешем
        $existingDocument = ProfileDocument::where('hash', $fileHash)
            ->where('user_id', $user->id)
            ->first();

        if ($existingDocument) {
            return response()->json([
                'message' => 'Документ з таким вмістом вже існує.',
                'document' => new ProfileDocumentResource($existingDocument),
            ], 409);
        }

        // Сохраняем файл ПОСЛЕ проверки хеша
        $filePath = $file->store('profile_documents', 'public');

        try {
            // Создаём новый документ
            $document = new ProfileDocument([
                'user_id' => $user->id,
                'file_path' => $filePath,
                'hash' => $fileHash,
                'sign_status' => 'pending',
                'service' => $request->input('service', 'diia'),
            ]);
            $document->save();

            return response()->json([
                'message' => 'Документ успішно завантажено.',
                'document' => new ProfileDocumentResource($document),
            ], 201);
        } catch (\Exception $e) {
            // Удаляем файл в случае ошибки
            Storage::disk('public')->delete($filePath);

            Log::error('Помилка при завантаженні документа', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Помилка при завантаженні документа.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Видалити документ
     */
    public function deleteDocument(Request $request, int $documentId): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $document = ProfileDocument::where('id', $documentId)
            ->where('user_id', $user->id)
            ->first();

        if (! $document) {
            return response()->json([
                'message' => 'Документ не знайдено.',
            ], 404);
        }

        try {
            // Удаляем файл с диска
            Storage::disk('public')->delete($document->file_path);

            // Удаляем подписанный файл, если есть
            if ($document->signed_file_path) {
                Storage::disk('public')->delete($document->signed_file_path);
            }

            // Удаляем запись из базы
            $document->delete();

            return response()->json([
                'message' => 'Документ успішно видалено.',
            ]);
        } catch (\Exception $e) {
            Log::error('Помилка при видаленні документа', [
                'user_id' => $user->id,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Помилка при видаленні документа.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Завантажити документ
     */
    public function downloadDocument(Request $request, int $documentId): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $document = ProfileDocument::where('id', $documentId)
            ->where('user_id', $user->id)
            ->first();

        if (! $document) {
            return response()->json([
                'message' => 'Документ не знайдено.',
            ], 404);
        }

        $filePath = storage_path('app/public/'.$document->file_path);

        if (! file_exists($filePath)) {
            return response()->json([
                'message' => 'Файл документа не знайдено на диску.',
            ], 404);
        }

        return response()->download($filePath);
    }

    /**
     * Отримати інформацію про конкретний документ
     */
    public function getDocument(Request $request, int $documentId): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $document = ProfileDocument::where('id', $documentId)
            ->where('user_id', $user->id)
            ->first();

        if (! $document) {
            return response()->json([
                'message' => 'Документ не знайдено.',
            ], 404);
        }

        return response()->json([
            'document' => new ProfileDocumentResource($document),
        ]);
    }

    /**
     * Оновити пароль користувача
     */
    public function updatePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        // Перевіряємо поточний пароль
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Поточний пароль невірний.',
                'errors' => [
                    'current_password' => ['Поточний пароль невірний.'],
                ],
            ], 422);
        }

        // Оновлюємо пароль
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Пароль успішно оновлено.',
        ]);
    }

    /**
     * Завантажити аватар користувача
     */
    public function uploadAvatar(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB max
        ]);

        /** @var User $user */
        $user = $request->user();

        $file = $request->file('avatar');

        // Видаляємо старий аватар, якщо є
        $profile = $user->profilePersonal;
        if ($profile && $profile->avatar) {
            Storage::disk('public')->delete($profile->avatar);
        }

        // Зберігаємо новий аватар
        $path = $file->store('avatars', 'public');

        // Оновлюємо або створюємо профіль
        if (! $profile) {
            $profile = new ProfilePersonal([
                'user_id' => $user->id,
                'full_name' => $user->name,
            ]);
        }
        $profile->avatar = $path;
        $profile->save();

        return response()->json([
            'message' => 'Аватар успішно оновлено.',
            'avatar_url' => Storage::disk('public')->url($path),
            'avatar_path' => $path,
        ]);
    }

    /**
     * Відправити запит на видалення профілю
     *
     * Якщо в профілі є незакриті фінансові операції,
     * він пройде перевірку модераторами перед видаленням.
     */
    public function requestDeletion(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Перевіряємо, чи є незакриті фінансові операції
        $hasPendingDonations = $user->donations()
            ->whereIn('status', ['pending'])
            ->exists();

        $hasActiveProjects = $user->projects()
            ->whereIn('status', ['moderation', 'announced', 'in_progress'])
            ->exists();

        if ($hasPendingDonations || $hasActiveProjects) {
            // Створюємо запит на модерацію
            $user->update([
                'deletion_requested_at' => now(),
            ]);

            // Повідомляємо адміністраторів
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'type' => 'system',
                'title' => 'Запит на видалення профілю',
                'message' => 'Ваш запит на видалення профілю отримано. Оскільки у вас є незакриті операції, він буде розглянутий модераторами.',
            ]);

            return response()->json([
                'message' => 'Запит на видалення профілю відправлено на модерацію.',
                'has_pending_operations' => true,
            ]);
        }

        // Якщо немає незакритих операцій - видаляємо одразу
        // Спочатку видаляємо пов'язані дані
        $user->profilePersonal?->delete();
        $user->profileLegal?->delete();
        $user->profileSocial?->delete();
        $user->profileDocuments()->delete();

        // Анонімізуємо донати
        $user->donations()->update([
            'user_id' => null,
            'is_anonymous' => true,
        ]);

        // Видаляємо токени
        $user->tokens()->delete();

        // Видаляємо користувача
        $user->delete();

        return response()->json([
            'message' => 'Ваш профіль успішно видалено.',
        ]);
    }
}
