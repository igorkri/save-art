<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * Лист підтвердження email мав завжди вести на дефолтний FRONTEND_URL
 * (save-art-web), незалежно від того, звідки прийшла реєстрація —
 * app/Http/Controllers/Api/V1/Auth/RegisterController.php тепер визначає
 * фронтенд по Origin/Referer запиту (App\Support\FrontendUrlResolver).
 */
class EmailVerificationFrontendUrlTest extends ApiTestCase
{
    public function test_register_from_art_ua_info_sends_verification_link_to_that_domain(): void
    {
        Notification::fake();

        $response = $this->withHeaders(array_merge($this->apiHeaders(), [
            'Origin' => 'https://art-ua-info.ddev.site:3000',
        ]))->postJson('/api/v1/art-ua-info/auth/register', [
            'name' => 'Test User',
            'email' => 'art-ua-info-user@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'art-ua-info-user@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_starts_with($mail->actionUrl, 'https://art-ua-info.ddev.site:3000/verify-email/');
        });
    }

    public function test_register_without_known_origin_falls_back_to_default_frontend_url(): void
    {
        Notification::fake();

        $response = $this->apiPost('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'default-frontend-user@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'default-frontend-user@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_starts_with($mail->actionUrl, rtrim(config('app.frontend_url'), '/').'/verify-email/');
        });
    }
}
