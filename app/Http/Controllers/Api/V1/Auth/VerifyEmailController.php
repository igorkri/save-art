<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\FrontendUrlResolver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class VerifyEmailController extends Controller
{
    /**
     * Підтвердити email за посиланням з листа
     *
     * @OA\Get(
     *     path="/v1/auth/email/verify/{id}/{hash}",
     *     operationId="verifyEmail",
     *     tags={"Auth"},
     *     summary="Підтвердження email за підписаним посиланням з листа",
     *     description="Викликається сторінкою /verify-email на фронтенді. Посилання одноразово підписане та дійсне 60 хвилин.",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="hash", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="expires", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="signature", in="query", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Email підтверджено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="redirect_path", type="string", example="/choose-role")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Посилання недійсне")
     * )
     */
    public function verify(string $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json([
                'message' => 'Посилання для підтвердження email недійсне',
            ], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $token = $user->createToken('email-verification')->plainTextToken;

        return response()->json([
            'message' => 'Email підтверджено',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'slug' => $user->slug,
            ],
            'token' => $token,
            'redirect_path' => '/choose-role',
        ]);
    }

    /**
     * Повторно надіслати лист підтвердження email
     *
     * @OA\Post(
     *     path="/v1/auth/email/verification-notification",
     *     operationId="resendEmailVerification",
     *     tags={"Auth"},
     *     summary="Повторна відправка листа підтвердження email",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(response=200, description="Лист надіслано"),
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email вже підтверджено']);
        }

        $user->notify(new VerifyEmailNotification(FrontendUrlResolver::resolve($request)));

        return response()->json(['message' => 'Лист для підтвердження email надіслано повторно']);
    }
}
