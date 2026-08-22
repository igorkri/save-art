<?php

namespace Tests\Feature\Filament;

use App\Enums\ModerationStatus;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\ImpersonationToken;
use App\Models\Project;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProjectModerateOnSiteActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_moderate_on_site_issues_self_login_grant_for_save_art_project(): void
    {
        $project = Project::factory()->create([
            'source' => ProjectSource::SaveArt,
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ListProjects::class)
            ->callTableAction('moderateOnSite', $project);

        $grant = ImpersonationToken::where('project_slug', $project->slug)->first();

        $this->assertNotNull($grant);
        $this->assertSame($this->admin->id, $grant->user_id);
        $this->assertSame($this->admin->id, $grant->created_by);
        $this->assertSame('save_art', $grant->target_app);
        $this->assertTrue($grant->isValid());

        $jsCalls = $component->effects['xjs'] ?? [];
        $this->assertNotEmpty($jsCalls);
        $this->assertStringContainsString('window.open', $jsCalls[0]['expression']);
        $this->assertStringContainsString($grant->token, $jsCalls[0]['expression']);
        $this->assertStringContainsString(str_replace('/', '\/', config('app.frontend_url')), $jsCalls[0]['expression']);
    }

    public function test_moderate_on_site_issues_self_login_grant_for_art_ua_info_project(): void
    {
        $project = Project::factory()->create([
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ListProjects::class)
            ->callTableAction('moderateOnSite', $project);

        $grant = ImpersonationToken::where('project_slug', $project->slug)->first();

        $this->assertNotNull($grant);
        $this->assertSame('art_ua_info_project', $grant->target_app);
        $this->assertSame("/works/{$project->slug}", $grant->redirectPath());

        $jsCalls = $component->effects['xjs'] ?? [];
        $this->assertStringContainsString(
            str_replace('/', '\/', config('services.art_ua_info_frontend_url')),
            $jsCalls[0]['expression']
        );
    }

    public function test_moderate_on_site_action_hidden_for_non_moderation_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Announced,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListProjects::class)
            ->assertTableActionHidden('moderateOnSite', $project);
    }
}
