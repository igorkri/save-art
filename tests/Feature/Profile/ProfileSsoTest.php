<?php

namespace Tests\Feature\Profile;

use App\Models\ProfileSsoToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api_key' => '']);
    }

    public function test_issue_sso_grant_requires_authentication(): void
    {
        $this->postJson('/api/v1/profile/sso-grant')
            ->assertUnauthorized();
    }

    public function test_issue_sso_grant_returns_url_with_allowed_redirect_path(): void
    {
        $user = User::factory()->artist()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile/sso-grant', [
            'redirect_path' => '/profile/donations',
        ]);

        $response->assertOk()->assertJsonStructure(['url']);

        $grant = ProfileSsoToken::where('user_id', $user->id)->first();
        $this->assertNotNull($grant);
        $this->assertSame('/profile/donations', $grant->redirect_path);
        $this->assertStringContainsString("/profile-sso/{$grant->token}", $response->json('url'));
    }

    public function test_issue_sso_grant_ignores_disallowed_redirect_path(): void
    {
        $user = User::factory()->artist()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/sso-grant', [
            'redirect_path' => 'https://evil.example.com',
        ])->assertOk();

        $grant = ProfileSsoToken::where('user_id', $user->id)->first();
        $this->assertSame('/profile/profile', $grant->redirect_path);
    }

    public function test_consuming_valid_grant_logs_user_into_web_guard_and_redirects(): void
    {
        $user = User::factory()->artist()->create();
        $grant = ProfileSsoToken::issue($user, '/profile/donations');

        $response = $this->get("/profile-sso/{$grant->token}");

        // Навмисно НЕ http-redirect (302) у цій самій відповіді — щойно
        // встановлена сесійна кука може не долетіти на наступний хоп того
        // самого cross-site ланцюжка редіректів у деяких браузерах. Тому
        // віддаємо HTML-сторінку з client-side редіректом (див.
        // SsoLoginController::consume).
        $response->assertOk();
        $response->assertSee('/profile/donations', false);
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($grant->fresh()->used_at);
    }

    public function test_consuming_grant_twice_fails_second_time(): void
    {
        $user = User::factory()->artist()->create();
        $grant = ProfileSsoToken::issue($user, '/profile/profile');

        $this->get("/profile-sso/{$grant->token}")->assertOk();

        $this->app['auth']->guard('web')->logout();

        $response = $this->get("/profile-sso/{$grant->token}");

        $response->assertRedirect(route('filament.profile.auth.login'));
        $this->assertGuest('web');
    }

    public function test_consuming_unknown_token_redirects_to_login(): void
    {
        $response = $this->get('/profile-sso/does-not-exist');

        $response->assertRedirect(route('filament.profile.auth.login'));
        $this->assertGuest('web');
    }

    public function test_consuming_grant_for_blocked_user_redirects_to_login(): void
    {
        $user = User::factory()->artist()->create(['is_blocked' => true]);
        $grant = ProfileSsoToken::issue($user, '/profile/profile');

        $response = $this->get("/profile-sso/{$grant->token}");

        $response->assertRedirect(route('filament.profile.auth.login'));
        $this->assertGuest('web');
    }
}
