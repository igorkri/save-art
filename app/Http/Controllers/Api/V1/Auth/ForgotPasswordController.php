<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\FrontendUrlResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

class ForgotPasswordController extends Controller
{
    /**
     * Відправити email для скидання пароля
     *
     * @OA\Post(
     *     path="/v1/auth/forgot-password",
     *     operationId="forgotPassword",
     *     tags={"Auth"},
     *     summary="Запит на скидання пароля",
     *     description="Надсилає email з посиланням для скидання пароля",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Посилання надіслано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'locale' => ['nullable', 'string', 'in:uk,en'],
        ]);

        app()->setLocale($data['locale'] ?? app()->getLocale());

        $frontendUrl = FrontendUrlResolver::resolve($request);

        $status = Password::sendResetLink(
            $request->only('email'),
            fn (User $user, string $token) => $user->notify(new ResetPasswordNotification($token, $frontendUrl))
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Посилання для скидання пароля відправлено на email',
        ]);
    }

    /**
     * Скинути пароль
     *
     * @OA\Post(
     *     path="/v1/auth/reset-password",
     *     operationId="resetPassword",
     *     tags={"Auth"},
     *     summary="Скидання пароля",
     *     description="Скидає пароль за допомогою токена з email",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Пароль змінено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error або невалідний токен")
     * )
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Пароль успішно змінено',
        ]);
    }
}
