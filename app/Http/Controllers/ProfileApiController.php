<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileLegalRequest;
use App\Http\Requests\UpdateProfilePersonalRequest;
use App\Http\Requests\UpdateProfileSocialRequest;
use App\Http\Requests\UploadProfileDocumentRequest;
use App\Http\Resources\ProfileDocumentResource;
use App\Http\Resources\ProfileLegalResource;
use App\Http\Resources\ProfilePersonalResource;
use App\Http\Resources\ProfileSocialResource;
use App\Models\ProfileDocument;
use App\Models\ProfileLegal;
use App\Models\ProfileSocial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;

/**
 * Profile API Controller.
 * Implements screens 03.7.1-03.7.4 from Figma specification.
 *
 * @OA\Tag(
 *     name="Profile",
 *     description="API для роботи з профілем користувача (03.7.1-03.7.4 Profile screens)"
 * )
 */
class ProfileApiController extends Controller
{
    /**
     * Отримати профіль поточного користувача
     *
     * @OA\Get(
     *     path="/v1/profile",
     *     operationId="getProfile",
     *     tags={"Profile"},
     *     summary="Отримати повний профіль",
     *     description="Повертає всі дані профілю: персональні, юридичні, соціальні та документи",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані профілю",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="slug", type="string", example="ivan-petrenko"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="role", type="string", example="user")
     *             ),
     *             @OA\Property(property="profilePersonal", ref="#/components/schemas/ProfilePersonal", nullable=true),
     *             @OA\Property(property="profileLegal", ref="#/components/schemas/ProfileLegal", nullable=true),
     *             @OA\Property(property="profileSocial", ref="#/components/schemas/ProfileSocial", nullable=true),
     *             @OA\Property(property="profileDocuments", type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/ProfileDocument")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ")
     * )
     */
    public function getProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['profileLegal', 'profileSocial', 'profileDocuments']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'slug' => $user->slug,
                'email' => $user->email,
                'role' => $user->role?->value,
            ],
            'profilePersonal' => new ProfilePersonalResource($user),
            'profileLegal' => $user->profileLegal ? new ProfileLegalResource($user->profileLegal) : null,
            'profileSocial' => $user->profileSocial ? new ProfileSocialResource($user->profileSocial) : null,
            'profileDocuments' => ProfileDocumentResource::collection($user->profileDocuments),
        ]);
    }

    /**
     * Оновити особистий профіль користувача
     *
     * @OA\Put(
     *     path="/v1/profile/personal",
     *     operationId="updateProfilePersonal",
     *     tags={"Profile"},
     *     summary="Оновити персональні дані (03.7.2)",
     *     description="Оновлює персональні дані профілю",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="avatar", type="string", nullable=true, example="avatars/user1.jpg", description="Шлях до аватара"),
     *             @OA\Property(property="full_name", type="object", description="Повне ім'я (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Іван Петренко"),
     *                 @OA\Property(property="en", type="string", example="Ivan Petrenko")
     *             ),
     *             @OA\Property(property="profession", type="object", description="Професія (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Художник"),
     *                 @OA\Property(property="en", type="string", example="Artist")
     *             ),
     *             @OA\Property(property="tags", type="object", description="Теги/спеціалізації (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="живопис, графіка"),
     *                 @OA\Property(property="en", type="string", example="painting, graphics")
     *             ),
     *             @OA\Property(property="country", type="object", description="Країна (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Україна"),
     *                 @OA\Property(property="en", type="string", example="Ukraine")
     *             ),
     *             @OA\Property(property="region", type="object", description="Регіон/область (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Київська область"),
     *                 @OA\Property(property="en", type="string", example="Kyiv region")
     *             ),
     *             @OA\Property(property="city", type="object", description="Місто (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Київ"),
     *                 @OA\Property(property="en", type="string", example="Kyiv")
     *             ),
     *              @OA\Property(property="postal_code", type="string", nullable=true, example="01001", description="Поштовий індекс"),
     *                 @OA\Property(property="profile_type", type="string", nullable=true, example="artist", description="Тип профілю: artist або patron"),
     *                 @OA\Property(property="description", type="object", description="Опис/біографія (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Український художник, працюю в жанрі абстракції"),
     *                 @OA\Property(property="en", type="string", example="Ukrainian artist, working in abstract genre")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Профіль оновлено",
     *
     *         @OA\JsonContent(@OA\Property(property="profilePersonal", ref="#/components/schemas/ProfilePersonal"))
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function updatePersonal(UpdateProfilePersonalRequest $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        // Обробка base64 аватара
        if (! empty($validated['avatar']) && str_starts_with($validated['avatar'], 'data:image/')) {
            $validated['avatar'] = $this->processBase64Avatar($validated['avatar'], $user);
        }

        $user->fill($validated);
        $user->save();

        return response()->json(['profilePersonal' => new ProfilePersonalResource($user)]);
    }

    /**
     * Створити особистий профіль користувача
     *
     * @OA\Post(
     *     path="/v1/profile/personal",
     *     operationId="createProfilePersonal",
     *     tags={"Profile"},
     *     summary="Створити персональні дані (03.7.2)",
     *     description="Створює персональні дані профілю",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="avatar", type="string", nullable=true, example="avatars/user1.jpg", description="Шлях до аватара"),
     *             @OA\Property(property="full_name", type="object", description="Повне ім'я (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Іван Петренко"),
     *                 @OA\Property(property="en", type="string", example="Ivan Petrenko")
     *             ),
     *             @OA\Property(property="profession", type="object", description="Професія (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Художник"),
     *                 @OA\Property(property="en", type="string", example="Artist")
     *             ),
     *             @OA\Property(property="tags", type="object", description="Теги/спеціалізації (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="живопис, графіка"),
     *                 @OA\Property(property="en", type="string", example="painting, graphics")
     *             ),
     *             @OA\Property(property="country", type="object", description="Країна (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Україна"),
     *                 @OA\Property(property="en", type="string", example="Ukraine")
     *             ),
     *             @OA\Property(property="region", type="object", description="Регіон/область (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Київська область"),
     *                 @OA\Property(property="en", type="string", example="Kyiv region")
     *             ),
     *             @OA\Property(property="city", type="object", description="Місто (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Київ"),
     *                 @OA\Property(property="en", type="string", example="Kyiv")
     *             ),
     *             @OA\Property(property="postal_code", type="string", nullable=true, example="01001", description="Поштовий індекс"),
     *             @OA\Property(property="profile_type", type="string", nullable=true, example="artist", description="Роль користувача"),
     *             @OA\Property(property="description", type="object", description="Опис/біографія (мультимовне)",
     *                 @OA\Property(property="uk", type="string", example="Український художник, працюю в жанрі абстракції"),
     *                 @OA\Property(property="en", type="string", example="Ukrainian artist, working in abstract genre")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Профіль створено",
     *
     *         @OA\JsonContent(@OA\Property(property="profilePersonal", ref="#/components/schemas/ProfilePersonal"))
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=409, description="Профіль вже існує"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function createPersonal(UpdateProfilePersonalRequest $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        // Обробка base64 аватара
        if (! empty($validated['avatar']) && str_starts_with($validated['avatar'], 'data:image/')) {
            $validated['avatar'] = $this->processBase64Avatar($validated['avatar'], $user);
        }

        $user->fill($validated);
        $user->save();

        return response()->json(['profilePersonal' => new ProfilePersonalResource($user)], 201);
    }

    /**
     * Оновити юридичний профіль користувача
     *
     * @OA\Put(
     *     path="/v1/profile/legal",
     *     operationId="updateProfileLegal",
     *     tags={"Profile"},
     *     summary="Оновити юридичні дані (03.7.1)",
     *     description="Оновлює юридичні дані профілю (опціонально)",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *
     *         @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH", description="Валюта"),
     *         @OA\Property(property="is_legal", type="boolean", example=true, description="Чи є юридичною особою"),
     *         @OA\Property(property="logo", type="string", nullable=true, example="logos/company.jpg", description="Логотип"),
     *         @OA\Property(property="name", type="object", description="Назва компанії (мультимовне)",
     *             @OA\Property(property="uk", type="string", example="ТОВ Мистецтво"),
     *             @OA\Property(property="en", type="string", example="Art LLC")
     *         ),
     *         @OA\Property(property="edrpou", type="string", example="12345678", description="Код ЄДРПОУ"),
     *         @OA\Property(property="authorized_person", type="object", description="Уповноважена особа (мультимовне)",
     *             @OA\Property(property="uk", type="string", example="Іван Петренко"),
     *             @OA\Property(property="en", type="string", example="Ivan Petrenko")
     *         ),
     *         @OA\Property(property="address", type="object", description="Адреса (мультимовне)",
     *             @OA\Property(property="uk", type="string", example="м. Київ, вул. Хрещатик, 1"),
     *             @OA\Property(property="en", type="string", example="Kyiv, Khreshchatyk str., 1")
     *         ),
     *         @OA\Property(property="phone", type="string", example="+380501234567", description="Телефон"),
     *         @OA\Property(property="email", type="string", format="email", example="company@example.com", description="Email")
     *     )),
     *
     *     @OA\Response(response=200, description="Профіль оновлено",
     *
     *         @OA\JsonContent(@OA\Property(property="profileLegal", ref="#/components/schemas/ProfileLegal"))
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function updateLegal(UpdateProfileLegalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $profile = $user->profileLegal;
        if (! $profile) {
            $profile = new ProfileLegal(['user_id' => $user->id]);
        }

        $validated = $request->validated();

        // Обробка base64 logo
        if (! empty($validated['logo']) && str_starts_with($validated['logo'], 'data:image/')) {
            $validated['logo'] = $this->processBase64Logo($validated['logo'], $profile);
        }

        $profile->fill($validated);
        $profile->save();

        return response()->json(['profileLegal' => new ProfileLegalResource($profile)]);
    }

    /**
     * Створити юридичний профіль користувача
     *
     * @OA\Post(
     *     path="/v1/profile/legal",
     *     operationId="createProfileLegal",
     *     tags={"Profile"},
     *     summary="Створити юридичні дані (03.7.1)",
     *     description="Створює юридичні дані профілю",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *
     *         @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH", description="Валюта"),
     *         @OA\Property(property="is_legal", type="boolean", example=true, description="Чи є юридичною особою"),
     *         @OA\Property(property="logo", type="string", nullable=true, example="logos/company.jpg", description="Логотип"),
     *         @OA\Property(property="name", type="object", description="Назва компанії (мультимовне)",
     *             @OA\Property(property="uk", type="string", example="ТОВ Мистецтво"),
     *             @OA\Property(property="en", type="string", example="Art LLC")
     *         ),
     *         @OA\Property(property="edrpou", type="string", example="12345678", description="Код ЄДРПОУ"),
     *         @OA\Property(property="authorized_person", type="object", description="Уповноважена особа (мультимовне)",
     *             @OA\Property(property="uk", type="string", example="Іван Петренко"),
     *             @OA\Property(property="en", type="string", example="Ivan Petrenko")
     *         ),
     *         @OA\Property(property="address", type="object", description="Адреса (мультимовне)",
     *             @OA\Property(property="uk", type="string", example="м. Київ, вул. Хрещатик, 1"),
     *             @OA\Property(property="en", type="string", example="Kyiv, Khreshchatyk str., 1")
     *         ),
     *         @OA\Property(property="phone", type="string", example="+380501234567", description="Телефон"),
     *         @OA\Property(property="email", type="string", format="email", example="company@example.com", description="Email")
     *     )),
     *
     *     @OA\Response(response=201, description="Профіль створено",
     *
     *         @OA\JsonContent(@OA\Property(property="profileLegal", ref="#/components/schemas/ProfileLegal"))
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=409, description="Профіль вже існує"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function createLegal(UpdateProfileLegalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user->profileLegal()->exists()) {
            return response()->json(['message' => __('api.profile.legal_exists')], 409);
        }

        $profile = new ProfileLegal(['user_id' => $user->id]);
        $validated = $request->validated();

        // Обробка base64 logo
        if (! empty($validated['logo']) && str_starts_with($validated['logo'], 'data:image/')) {
            $validated['logo'] = $this->processBase64Logo($validated['logo'], $profile);
        }

        $profile->fill($validated);
        $profile->save();

        return response()->json(['profileLegal' => new ProfileLegalResource($profile)], 201);
    }

    /**
     * Оновити соціальний профіль користувача
     *
     * @OA\Put(
     *     path="/v1/profile/social",
     *     operationId="updateProfileSocial",
     *     tags={"Profile"},
     *     summary="Оновити соцмережі (03.7.3)",
     *     description="Оновлює посилання на соціальні мережі",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *
     *         @OA\Property(property="website", type="string", example="https://myart.com"),
     *         @OA\Property(property="instagram", type="string", example="https://instagram.com/myart"),
     *         @OA\Property(property="facebook", type="string", example="https://facebook.com/myart"),
     *         @OA\Property(property="youtube", type="string", example="https://youtube.com/@myart")
     *     )),
     *
     *     @OA\Response(response=200, description="Профіль оновлено"),
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
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

        return response()->json(['profileSocial' => new ProfileSocialResource($profile)]);
    }

    /**
     * Створити соціальний профіль користувача
     *
     * @OA\Post(
     *     path="/v1/profile/social",
     *     operationId="createProfileSocial",
     *     tags={"Profile"},
     *     summary="Створити соцмережі (03.7.3)",
     *     description="Створює посилання на соціальні мережі",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *
     *         @OA\Property(property="website", type="string", example="https://myart.com"),
     *         @OA\Property(property="instagram", type="string", example="https://instagram.com/myart")
     *     )),
     *
     *     @OA\Response(response=201, description="Профіль створено"),
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=409, description="Профіль вже існує"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
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

        return response()->json(['profileSocial' => new ProfileSocialResource($profile)], 201);
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
     *
     * @OA\Put(
     *     path="/v1/profile/password",
     *     operationId="updateProfilePassword",
     *     tags={"Profile"},
     *     summary="Змінити пароль (03.7.4)",
     *     description="Змінює пароль користувача. Екран 03.7.4 Safety.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"email", "current_password", "password", "password_confirmation"},
     *
     *         @OA\Property(property="email", type="string", format="email", example="user@example.com", description="Email користувача для підтвердження"),
     *         @OA\Property(property="current_password", type="string", format="password", example="oldPassword123", description="Поточний пароль"),
     *         @OA\Property(property="password", type="string", format="password", example="newPassword456", description="Новий пароль (мін. 8 символів, великі/малі літери, цифри)"),
     *         @OA\Property(property="password_confirmation", type="string", format="password", example="newPassword456", description="Підтвердження нового пароля")
     *     )),
     *
     *     @OA\Response(response=200, description="Пароль змінено",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Пароль успішно оновлено."))
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=422, description="Невірний email, поточний пароль або помилка валідації")
     * )
     */
    public function updatePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        // Перевіряємо email
        if ($validated['email'] !== $user->email) {
            return response()->json([
                'message' => 'Email не співпадає з вашим обліковим записом.',
                'errors' => [
                    'email' => ['Email не співпадає з вашим обліковим записом.'],
                ],
            ], 422);
        }

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
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Зберігаємо новий аватар
        $path = $file->store('avatars', 'public');

        // Оновлюємо аватар користувача
        $user->avatar = $path;
        $user->save();

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

    /**
     * Обробка base64 аватара - зберігає зображення та повертає шлях
     */
    private function processBase64Avatar(string $base64Image, User $user): string
    {
        // Видаляємо старий аватар, якщо є
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Парсимо base64 дані
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $extension = $matches[1];
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        } else {
            $extension = 'jpg';
        }

        // Декодуємо base64
        $imageData = base64_decode($base64Image);

        // Генеруємо унікальне ім'я файлу
        $filename = 'avatars/'.uniqid().'_'.time().'.'.$extension;

        // Зберігаємо файл
        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }

    /**
     * Обробка base64 logo - зберігає зображення та повертає шлях
     */
    private function processBase64Logo(string $base64Image, ?ProfileLegal $profile = null): string
    {
        // Видаляємо старий logo, якщо є
        if ($profile && $profile->logo) {
            Storage::disk('public')->delete($profile->logo);
        }

        // Парсимо base64 дані
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $extension = $matches[1];
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        } else {
            $extension = 'jpg';
        }

        // Декодуємо base64
        $imageData = base64_decode($base64Image);

        // Генеруємо унікальне ім'я файлу
        $filename = 'logos/'.uniqid().'_'.time().'.'.$extension;

        // Зберігаємо файл
        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }
}
