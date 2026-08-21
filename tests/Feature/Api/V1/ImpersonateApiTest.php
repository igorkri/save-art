<?php

namespace Tests\Feature\Api\V1;

use App\Models\ImpersonationToken;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;

class ImpersonateApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_exchange_valid_grant_for_a_bearer_token(): void
    {
        $admin = User::factory()->create();
        $grant = ImpersonationToken::issue($this->user, $admin);

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange");

        $response->assertOk()
            ->assertJsonPath('user.id', $this->user->id)
            ->assertJsonPath('redirect_path', '/profile/private')
            ->assertJsonStructure(['token']);

        $this->assertNotNull($grant->fresh()->used_at);
    }

    public function test_exchange_redirects_to_the_project_when_grant_has_a_project_slug(): void
    {
        $admin = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $grant = ImpersonationToken::issue($this->user, $admin, $project->slug);

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange");

        $response->assertOk()
            ->assertJsonPath('redirect_path', "/profile/private/{$project->slug}");
    }

    public function test_project_preview_grant_redirects_to_the_public_project_page(): void
    {
        $issuer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $grant = ImpersonationToken::issue(
            $this->user,
            $issuer,
            $project->slug,
            'save_art_project_preview',
        );

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange");

        $response->assertOk()
            ->assertJsonPath('is_project_preview', true)
            ->assertJsonPath('redirect_path', "/project/{$project->slug}");

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'project-preview',
            'abilities' => '["project:preview"]',
        ]);
    }

    public function test_exchange_redirects_to_art_ua_info_profile_for_that_target_app(): void
    {
        $admin = User::factory()->create();
        $grant = ImpersonationToken::issue($this->user, $admin, null, 'art_ua_info');

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange");

        $response->assertOk()
            ->assertJsonPath('redirect_path', "/profile/{$this->user->slug}");
    }

    public function test_exchange_redirects_to_art_ua_info_project_edit_when_grant_has_a_project_slug(): void
    {
        $admin = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $grant = ImpersonationToken::issue($this->user, $admin, $project->slug, 'art_ua_info');

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange");

        $response->assertOk()
            ->assertJsonPath('redirect_path', "/profile/{$this->user->slug}/edit-project?edit={$project->slug}");
    }

    public function test_grant_cannot_be_used_twice(): void
    {
        $admin = User::factory()->create();
        $grant = ImpersonationToken::issue($this->user, $admin);

        $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange")
            ->assertOk();

        $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange")
            ->assertNotFound();
    }

    public function test_expired_grant_cannot_be_exchanged(): void
    {
        $admin = User::factory()->create();
        $grant = ImpersonationToken::issue($this->user, $admin);
        $grant->update(['expires_at' => Carbon::now()->subMinute()]);

        $this->withHeaders($this->apiHeaders())
            ->postJson("/api/v1/auth/impersonate/{$grant->token}/exchange")
            ->assertNotFound();
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/auth/impersonate/does-not-exist/exchange')
            ->assertNotFound();
    }
}
