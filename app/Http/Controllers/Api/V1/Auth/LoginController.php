<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Авторизація користувача
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
                'avatar_url' => $user->avatar ? \Storage::url($user->avatar) : null,
                'created_at' => $user->created_at->toISOString(),
            ],
        ]);
    }
}
