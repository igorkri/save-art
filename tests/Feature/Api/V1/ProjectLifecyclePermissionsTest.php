<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\ProjectStage;
use App\Models\User;

class ProjectLifecyclePermissionsTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_editing_pending_moderation_project_returns_it_to_draft(): void
    {
        $project = Project::factory()->for($this->user)->moderation()->create([
            'status_moderation' => ModerationStatus::Pending,
        ]);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", ['title' => 'Виправлена назва'])
            ->assertOk();

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_processing_project_stage_cannot_be_changed(): void
    {
        $project = Project::factory()->for($this->user)->moderation()->create([
            'status_moderation' => ModerationStatus::Processing,
        ]);
        $stage = ProjectStage::factory()->for($project)->create();

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->slug}/stages/{$stage->id}/start")
            ->assertUnprocessable();

        $this->assertSame(StageStatus::Planned, $stage->fresh()->status);
    }

    public function test_stage_completion_requires_actual_budget_and_documents(): void
    {
        $project = Project::factory()->for($this->user)->inProgress()->create();
        $stage = ProjectStage::factory()->for($project)->inProgress()->create();

        $url = "/api/v1/my/projects/{$project->slug}/stages/{$stage->id}/complete";

        $this->withHeaders($this->authHeaders())->postJson($url)->assertUnprocessable();

        $stage->update([
            'budget_actual' => 100,
            'documents' => [['file' => 'proof.pdf']],
        ]);

        $this->withHeaders($this->authHeaders())->postJson($url)->assertOk();
        $this->assertSame(StageStatus::Completed, $stage->fresh()->status);
    }

    public function test_bonus_cannot_be_updated_after_publication(): void
    {
        $project = Project::factory()->for($this->user)->announced()->create();
        $bonus = ProjectBonus::factory()->for($project)->create();

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}/bonuses/{$bonus->id}", [
                'description' => 'Спроба зміни',
            ])
            ->assertUnprocessable();
    }

    public function test_editing_bonus_in_pending_queue_returns_project_to_draft(): void
    {
        $project = Project::factory()->for($this->user)->moderation()->create([
            'status_moderation' => ModerationStatus::Pending,
        ]);
        $bonus = ProjectBonus::factory()->for($project)->create();

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}/bonuses/{$bonus->id}", [
                'description' => 'Виправлений бонус',
            ])
            ->assertOk();

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_rejected_project_cannot_be_resubmitted(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Rejected,
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->slug}/submit")
            ->assertUnprocessable();
    }

    public function test_announced_project_cannot_be_started_by_owner_through_resume_endpoint(): void
    {
        $project = Project::factory()->for($this->user)->announced()->create();

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->slug}/resume")
            ->assertUnprocessable();

        $this->assertSame(ProjectStatus::Announced, $project->fresh()->status);
    }

    public function test_new_project_can_be_deleted_by_owner(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::New,
        ]);

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$project->slug}")
            ->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
