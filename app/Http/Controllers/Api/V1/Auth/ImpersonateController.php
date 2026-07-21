<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationToken;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class ImpersonateController extends Controller
{
    /**
     * Обміняти одноразовий грант з адмінки на справжній Bearer-токен
     *
     * @OA\Post(
     *     path="/v1/auth/impersonate/{token}/exchange",
     *     operationId="exchangeImpersonationToken",
     *     tags={"Auth"},
     *     summary="Обмін гранту на вхід під користувачем на Bearer-токен",
     *     description="Використовується сторінкою /impersonate/{token} на фронтенді одразу після переходу з адмінки. Грант одноразовий і живе 2 хвилини.",
     *     security={{"apiKey":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішний обмін",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Авторизація успішна"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Іван Петренко"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="slug", type="string", example="ivan-petrenko"),
     *                 @OA\Property(property="role", type="string", example="user")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|abc123..."),
     *             @OA\Property(property="redirect_path", type="string", example="/profile/private")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Грант не знайдено, вже використано або протермінувався")
     * )
     */
    public function exchange(string $token): JsonResponse
    {
        $grant = ImpersonationToken::where('token', $token)->first();

        if (! $grant || ! $grant->isValid()) {
            return response()->json([
                'message' => 'Посилання для входу недійсне або вже використане',
            ], 404);
        }

        // Одноразовий — одразу позначаємо використаним, незалежно від подальшого результату
        $grant->update(['used_at' => now()]);

        $user = $grant->user;

        $apiToken = $user->createToken('impersonation-by-'.$grant->created_by)->plainTextToken;

        return response()->json([
            'message' => 'Авторизація успішна',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'slug' => $user->slug,
                'role' => $user->role->value,
            ],
            'token' => $apiToken,
            'redirect_path' => $grant->redirectPath(),
        ]);
    }
}
