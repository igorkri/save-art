<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Get Google OAuth redirect URL.
     */
    public function googleRedirect(): JsonResponse
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Handle Google OAuth callback.
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

            $googleUser = Socialite::driver('google')
                ->stateless()
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
            $user = User::create([
                'name' => $socialUser->getName() ?? $email,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::User,
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
        $response = Socialite::driver('google')
            ->stateless()
            ->getAccessTokenResponse($code);

        return $response['access_token'] ?? '';
    }
}
