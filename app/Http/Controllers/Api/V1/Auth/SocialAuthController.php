<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\ProfileType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use OpenApi\Annotations as OA;

class SocialAuthController extends Controller
{
    /**
     * Get Google OAuth redirect URL.
     *
     * @OA\Get(
     *     path="/v1/auth/google/redirect",
     *     operationId="googleRedirect",
     *     tags={"Auth"},
     *     summary="Google OAuth URL",
     *     description="Повертає URL для редиректу на Google OAuth",
     *
     *     @OA\Response(
     *         response=200,
     *         description="OAuth URL",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="url", type="string", format="uri")
     *         )
     *     )
     * )
     */
    public function googleRedirect(): JsonResponse
    {
        $url = $this->googleDriver()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Handle Google OAuth callback.
     *
     * @OA\Post(
     *     path="/v1/auth/google/callback",
     *     operationId="googleCallback",
     *     tags={"Auth"},
     *     summary="Google OAuth callback",
     *     description="Обробляє callback від Google OAuth, створює або авторизує користувача",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="code", type="string", description="Authorization code від Google"),
     *             @OA\Property(property="access_token", type="string", description="Access token від Google (альтернатива code)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Авторизація успішна",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Authorization code is required"),
     *     @OA\Response(response=403, description="Обліковий запис заблоковано"),
     *     @OA\Response(response=422, description="Не вдалося отримати email"),
     *     @OA\Response(response=500, description="Помилка авторизації")
     * )
     */
    public function googleCallback(Request $request): JsonResponse
    {
        try {
            // For SPA, we receive the code from frontend
            $code = $request->input('code');

            if (! $code) {
                return response()->json([
                    'message' => 'Authorization code is required.',
                ], 400);
            }

            $googleUser = $this->googleDriver()
                ->userFromToken($request->input('access_token') ?? $this->getAccessToken($code));

            return $this->handleSocialUser($googleUser, 'google');
        } catch (\Exception $e) {
            Log::error('Google OAuth error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Помилка авторизації через Google.',
            ], 500);
        }
    }

    /**
     * Handle social user login/registration.
     *
     * @param  \Laravel\Socialite\Contracts\User  $socialUser
     */
    protected function handleSocialUser($socialUser, string $provider): JsonResponse
    {
        $email = $socialUser->getEmail();

        if (! $email) {
            return response()->json([
                'message' => 'Не вдалося отримати email з облікового запису.',
            ], 422);
        }

        // Find or create user
        $user = User::where('email', $email)->first();

        if ($user) {
            // Existing user - check if blocked
            if ($user->is_blocked) {
                return response()->json([
                    'message' => 'Ваш обліковий запис заблоковано.',
                ], 403);
            }
        } else {
            // New user - create account
            $userName = $socialUser->getName() ?? $email;
            // profile_type = Artist за замовчуванням — так само як і при звичайній
            // реєстрації (Api\V1\Auth\RegisterController): без дефолту доступ до
            // Filament-панелі "profile" (User::canAccessPanel, лише isArtist())
            // одразу впирався в 403. Хто хоче бути меценатом — міняє тип профілю
            // на /choose-role (updateUserType).
            $user = User::create([
                'full_name' => $userName,
                'email' => $email,
                'avatar' => $this->downloadGoogleAvatar($socialUser->getAvatar()),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::User,
                'profile_type' => ProfileType::Artist,
                'slug' => Str::slug($userName).'-'.Str::random(6),
            ]);
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Авторизація успішна.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Exchange authorization code for access token.
     */
    protected function getAccessToken(string $code): string
    {
        $response = $this->googleDriver()->getAccessTokenResponse($code);

        return $response['access_token'] ?? '';
    }

    /**
     * Socialite Google-драйвер з redirect_uri цього сайту. OAuth-код, отриманий
     * з певним redirect_uri, обмінюється на токен лише з тим самим redirect_uri
     * (вимога протоколу) — тому і googleRedirect(), і getAccessToken() мають
     * використовувати один і той самий драйвер із цього методу.
     *
     * @return GoogleProvider
     */
    protected function googleDriver()
    {
        $driver = Socialite::driver('google')->stateless();

        if ($redirectUri = $this->googleRedirectUri()) {
            $driver = $driver->redirectUrl($redirectUri);
        }

        return $driver;
    }

    /**
     * redirect_uri для цього сайту. За замовчуванням — те, що вже сконфігуроване
     * для Socialite (services.google.redirect, save-art). Перевизначається в
     * App\Http\Controllers\Api\V1\ArtUaInfo\SocialAuthController для art-ua-info,
     * бо Google OAuth вимагає точного збігу з redirect_uri, зареєстрованим у
     * Google Cloud Console для конкретного домену.
     */
    protected function googleRedirectUri(): ?string
    {
        return config('services.google.redirect');
    }

    /**
     * Завантажити аватар з Google і зберегти його на диску 'public', щоб поле
     * User::$avatar лишалось відносним шляхом (як і при ручному завантаженні
     * через ProfileApiController::uploadAvatar), а не зовнішнім URL, що
     * протухне/стане недоступним.
     */
    protected function downloadGoogleAvatar(?string $avatarUrl): ?string
    {
        if (! $avatarUrl) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get($avatarUrl);

            if (! $response->successful()) {
                return null;
            }

            $extension = str_contains($response->header('Content-Type'), 'png') ? 'png' : 'jpg';
            $path = 'avatars/'.Str::random(20).'.'.$extension;

            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Failed to download Google avatar', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
