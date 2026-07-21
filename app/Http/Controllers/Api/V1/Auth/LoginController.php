<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

class LoginController extends Controller
{
    /**
     * Авторизація користувача
     *
     * @OA\Post(
     *     path="/v1/auth/login",
     *     operationId="login",
     *     tags={"Auth"},
     *     summary="Авторизація користувача",
     *     description="Авторизація користувача за email та паролем. Повертає Bearer токен для подальших запитів.",
     *     security={{"apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 15 Pro")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішна авторизація",
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
     *             @OA\Property(property="token", type="string", example="1|abc123...")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Невалідний або відсутній API ключ",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Invalid or missing API key.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Невірні дані авторизації",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Невірний email або пароль"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Невірний email або пароль'],
            ]);
        }

        // Створюємо токен для API
        $token = $user->createToken(
            $data['device_name'] ?? 'api-token'
        )->plainTextToken;

        return response()->json([
            'message' => 'Авторизація успішна',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'slug' => $user->slug,
                'role' => $user->role->value,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Вихід (видалення поточного токена)
     *
     * @OA\Post(
     *     path="/v1/auth/logout",
     *     operationId="logout",
     *     tags={"Auth"},
     *     summary="Вихід з системи",
     *     description="Видаляє поточний токен авторизації",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішний вихід",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Вихід виконано успішно")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Вихід виконано успішно',
        ]);
    }

    /**
     * Отримати дані поточного користувача
     *
     * @OA\Get(
     *     path="/v1/auth/me",
     *     operationId="me",
     *     tags={"Auth"},
     *     summary="Поточний користувач",
     *     description="Повертає дані авторизованого користувача",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані користувача",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Іван Петренко"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="slug", type="string", example="ivan-petrenko"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="avatar_url", type="string", nullable=true),
     *                 @OA\Property(property="has_seen_new_project_hint", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function me(): JsonResponse
    {
        $user = request()->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'slug' => $user->slug,
                'role' => $user->role->value,
                'profile_type' => $user->profile_type?->value,
                'avatar_url' => $user->avatar ? \Storage::url($user->avatar) : null,
                'has_seen_new_project_hint' => $user->has_seen_new_project_hint,
                'created_at' => $user->created_at->toISOString(),
            ],
        ]);
    }
}
