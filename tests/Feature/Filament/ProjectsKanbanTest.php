<?php

namespace Tests\Feature\Filament;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\ProjectsKanban;
use App\Models\ImpersonationToken;
use App\Models\Project;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProjectsKanbanTest extends TestCase
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

    public function test_kanban_page_renders_without_errors(): void
    {
        Project::factory()->create(['status' => ProjectStatus::Draft]);
        Project::factory()->moderation()->create();
        Project::factory()->announced()->create();

        $this->actingAs($this->admin);

        Livewire::test(ProjectsKanban::class)
            ->assertStatus(200);
    }

    public function test_moving_project_to_allowed_status_updates_it(): void
    {
        $project = Project::factory()->moderation()->create();

        $this->actingAs($this->admin);

        Livewire::test(ProjectsKanban::class)
            ->call('moveProject', $project->id, ProjectStatus::Announced->value)
            ->assertNotified();

        $this->assertSame(ProjectStatus::Announced, $project->fresh()->status);
    }

    public function test_moving_project_to_disallowed_status_keeps_it_unchanged(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Draft]);

        $this->actingAs($this->admin);

        Livewire::test(ProjectsKanban::class)
            ->call('moveProject', $project->id, ProjectStatus::Completed->value)
            ->assertNotified();

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_view_action_shows_project_details(): void
    {
        $project = Project::factory()->create([
            'title' => ['uk' => 'Тестовий проєкт', 'en' => 'Test project'],
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ProjectsKanban::class)
            ->mountAction('view', ['project' => $project->id])
            ->assertSee('Тестовий проєкт');
    }

    public function test_view_action_can_change_status(): void
    {
        $project = Project::factory()->moderation()->create();

        $this->actingAs($this->admin);

        Livewire::test(ProjectsKanban::class)
            ->mountAction('view', ['project' => $project->id])
            ->call('moveProject', $project->id, ProjectStatus::Announced->value)
            ->assertNotified();

        $this->assertSame(ProjectStatus::Announced, $project->fresh()->status);
    }

    public function test_start_review_moves_pending_project_to_processing(): void
    {
        $project = Project::factory()->moderation()->create();

        $this->actingAs($this->admin);

        Livewire::test(ProjectsKanban::class)
            ->call('startReview', $project->id)
            ->assertNotified();

        $this->assertSame(ModerationStatus::Processing, $project->fresh()->status_moderation);
    }

    public function test_start_review_fails_for_already_processing_project(): void
    {
        $project = Project::factory()->moderation()->create([
            'status_moderation' => ModerationStatus::Processing,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ProjectsKanban::class)
            ->call('startReview', $project->id)
            ->assertNotified();

        $this->assertSame(ModerationStatus::Processing, $project->fresh()->status_moderation);
    }

    public function test_impersonate_author_issues_a_grant_and_opens_frontend_in_new_tab(): void
    {
        $project = Project::factory()->announced()->create();

        $this->actingAs($this->admin);

        $component = Livewire::test(ProjectsKanban::class)
            ->call('impersonateAuthor', $project->id);

        $grant = ImpersonationToken::where('user_id', $project->user_id)
            ->where('project_slug', $project->slug)
            ->first();

        $this->assertNotNull($grant);
        $this->assertSame($this->admin->id, $grant->created_by);
        $this->assertTrue($grant->isValid());

        // window.open(...) виконується через Livewire js()-ефект (нова вкладка, без full-page redirect)
        $jsCalls = $component->effects['xjs'] ?? [];
        $this->assertNotEmpty($jsCalls);
        $this->assertStringContainsString('window.open', $jsCalls[0]['expression']);
        $this->assertStringContainsString($grant->token, $jsCalls[0]['expression']);
    }
}
