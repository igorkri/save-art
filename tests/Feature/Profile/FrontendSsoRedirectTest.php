<?php

namespace Tests\Feature\Profile;

use App\Models\ImpersonationToken;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendSsoRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.frontend_url' => 'https://save-art-web.test:5173',
            'services.art_ua_info_frontend_url' => 'https://art-ua-info.test:3000',
        ]);
    }

    public function test_profile_menu_contains_both_frontend_sso_links_opened_in_new_tabs(): void
    {
        $user = User::factory()->artist()->profileCompleted()->create();

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));

        $this->get('/profile')
            ->assertOk()
            ->assertSee(__('profile_panel.navigation.save_art'))
            ->assertSee(__('profile_panel.navigation.art_ua_info'))
            ->assertSee(route('profile.frontend-sso', ['application' => 'save-art']), escape: false)
            ->assertSee(route('profile.frontend-sso', ['application' => 'art-ua-info']), escape: false)
            ->assertSee('target="_blank"', escape: false);
    }

    public function test_save_art_link_issues_self_login_grant_and_redirects_to_frontend(): void
    {
        $user = User::factory()->artist()->create();

        $response = $this->actingAs($user)
            ->get(route('profile.frontend-sso', ['application' => 'save-art']));

        $grant = ImpersonationToken::query()->sole();

        $this->assertSame($user->id, $grant->user_id);
        $this->assertSame($user->id, $grant->created_by);
        $this->assertSame('save_art', $grant->target_app);
        $response->assertRedirect("https://save-art-web.test:5173/impersonate/{$grant->token}");
    }

    public function test_art_ua_info_link_issues_self_login_grant_and_redirects_to_frontend(): void
    {
        $user = User::factory()->artist()->create();

        $response = $this->actingAs($user)
            ->get(route('profile.frontend-sso', ['application' => 'art-ua-info']));

        $grant = ImpersonationToken::query()->sole();

        $this->assertSame($user->id, $grant->user_id);
        $this->assertSame($user->id, $grant->created_by);
        $this->assertSame('art_ua_info', $grant->target_app);
        $response->assertRedirect("https://art-ua-info.test:3000/impersonate/{$grant->token}");
    }

    public function test_frontend_can_exchange_self_login_grant_for_authorization_token(): void
    {
        config(['services.api_key' => 'test-api-key']);
        $user = User::factory()->artist()->create();

        $this->actingAs($user)
            ->get(route('profile.frontend-sso', ['application' => 'save-art']))
            ->assertRedirect();

        $grant = ImpersonationToken::query()->sole();

        $this->withHeader('X-Api-Key', 'test-api-key')
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange")
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('redirect_path', '/')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'frontend-sso-save_art',
            'abilities' => '["*"]',
        ]);
    }

    public function test_frontend_sso_link_requires_profile_access(): void
    {
        $patron = User::factory()->patron()->create();

        $this->actingAs($patron)
            ->get(route('profile.frontend-sso', ['application' => 'save-art']))
            ->assertForbidden();

        $this->assertDatabaseCount('impersonation_tokens', 0);
    }

    public function test_guest_is_redirected_to_profile_login(): void
    {
        $this->get(route('profile.frontend-sso', ['application' => 'save-art']))
            ->assertRedirect(route('filament.profile.auth.login'));

        $this->assertDatabaseCount('impersonation_tokens', 0);
    }
}
