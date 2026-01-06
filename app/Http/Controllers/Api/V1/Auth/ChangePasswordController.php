<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

class ChangePasswordController extends Controller
{
    /**
     * Змінити пароль поточного користувача
     *
     * @OA\Put(
     *     path="/v1/auth/change-password",
     *     operationId="changePassword",
     *     tags={"Auth"},
     *     summary="Зміна пароля",
     *     description="Зміна пароля авторизованого користувача. Потрібно вказати поточний пароль та новий пароль з підтвердженням.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"current_password", "password", "password_confirmation"},
     *
     *             @OA\Property(property="current_password", type="string", format="password", example="oldPassword123"),
     *             @OA\Property(property="password", type="string", format="password", example="newPassword456"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newPassword456")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Пароль успішно змінено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Пароль успішно змінено")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Помилка валідації або невірний поточний пароль",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Поточний пароль невірний."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="current_password", type="array",
     *
     *                     @OA\Items(type="string", example="Поточний пароль невірний.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизовано"
     *     )
     * )
     */
    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
            'message' => 'Пароль успішно змінено.',
        ]);
    }
}
