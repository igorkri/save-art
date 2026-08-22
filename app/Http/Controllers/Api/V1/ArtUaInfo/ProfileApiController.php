<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileDocumentResource;
use App\Http\Resources\ProfileLegalResource;
use App\Http\Resources\ProfilePersonalResource;
use App\Http\Resources\ProfileSocialResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * Копія підмножини методів App\Http\Controllers\ProfileApiController (save-art),
 * якою реально користується art-ua-info: getProfile.
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
    public function getProfile(Request $request): JsonResponse
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
}
