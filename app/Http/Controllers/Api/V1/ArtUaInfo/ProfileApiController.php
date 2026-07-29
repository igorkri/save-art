<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfilePersonalRequest;
use App\Http\Requests\UpdateProfileSocialRequest;
use App\Http\Resources\ProfileDocumentResource;
use App\Http\Resources\ProfileLegalResource;
use App\Http\Resources\ProfilePersonalResource;
use App\Http\Resources\ProfileSocialResource;
use App\Models\ProfileSocial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * Копія підмножини методів App\Http\Controllers\ProfileApiController (save-art),
 * якою реально користується art-ua-info: getProfile, updatePersonal, updateSocial.
 *
 * @OA\Tag(
 *     name="Profile (Art-UA Info)",
 *     description="API для роботи з профілем користувача art-ua-info"
 * )
 */
class ProfileApiController extends Controller
{
    /**
     * Отримати профіль поточного користувача
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/profile",
     *     operationId="artUaInfoGetProfile",
     *     tags={"Profile (Art-UA Info)"},
     *     summary="Отримати повний профіль",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(response=200, description="Дані профілю"),
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
                'has_seen_new_project_hint' => $user->has_seen_new_project_hint,
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
     *     path="/v1/art-ua-info/profile/personal",
     *     operationId="artUaInfoUpdateProfilePersonal",
     *     tags={"Profile (Art-UA Info)"},
     *     summary="Оновити персональні дані",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(response=200, description="Профіль оновлено"),
     *     @OA\Response(response=401, description="Неавторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function updatePersonal(UpdateProfilePersonalRequest $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        if (! empty($validated['avatar'])) {
            $validated['avatar'] = str_starts_with($validated['avatar'], 'data:image/')
                ? $this->processBase64Avatar($validated['avatar'], $user)
                : $this->normalizeAvatarPath($validated['avatar']);
        }

        $user->fill($validated);
        $user->save();

        return response()->json(['profilePersonal' => new ProfilePersonalResource($user)]);
    }

    /**
     * Оновити соціальний профіль користувача
     *
     * @OA\Put(
     *     path="/v1/art-ua-info/profile/social",
     *     operationId="artUaInfoUpdateProfileSocial",
     *     tags={"Profile (Art-UA Info)"},
     *     summary="Оновити соцмережі",
     *     security={{"sanctum":{}, "apiKey":{}}},
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
     * Нормалізує шлях до аватара: якщо прийшов повний URL, повертає відносний
     * шлях диска "public", щоб уникнути подвоєння /storage/ при повторному збереженні.
     */
    private function normalizeAvatarPath(string $avatar): string
    {
        $storageUrl = Storage::disk('public')->url('');

        if (str_starts_with($avatar, $storageUrl)) {
            return ltrim(substr($avatar, strlen($storageUrl)), '/');
        }

        if (str_starts_with($avatar, '/storage/')) {
            return ltrim(substr($avatar, strlen('/storage/')), '/');
        }

        return $avatar;
    }

    /**
     * Обробка base64 аватара - зберігає зображення та повертає шлях
     */
    private function processBase64Avatar(string $base64Image, User $user): string
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $extension = $matches[1];
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        } else {
            $extension = 'jpg';
        }

        $imageData = base64_decode($base64Image);

        $filename = 'avatars/'.uniqid().'_'.time().'.'.$extension;

        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }
}
