<?php

namespace Tests\Unit\Services;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectWorkflowService;
    }

    public function test_can_transition_from_draft_to_moderation(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Draft]);

        $this->assertTrue($this->service->canTransition($project, ProjectStatus::Moderation));
    }

    public function test_cannot_transition_from_draft_to_announced(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Draft]);

        $this->assertFalse($this->service->canTransition($project, ProjectStatus::Announced));
    }

    public function test_can_transition_from_moderation_to_announced(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Moderation]);

        $this->assertTrue($this->service->canTransition($project, ProjectStatus::Announced));
    }

    public function test_can_transition_from_moderation_to_rejected(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Moderation]);

        $this->assertTrue($this->service->canTransition($project, ProjectStatus::Rejected));
    }

    public function test_can_transition_from_rejected_to_moderation(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Rejected]);

        $this->assertTrue($this->service->canTransition($project, ProjectStatus::Moderation));
    }

    public function test_can_transition_from_announced_to_in_progress(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Announced]);

        $this->assertTrue($this->service->canTransition($project, ProjectStatus::InProgress));
    }

    public function test_can_transition_from_in_progress_to_completed(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::InProgress]);

        $this->assertTrue($this->service->canTransition($project, ProjectStatus::Completed));
    }

    public function test_cannot_transition_from_sold(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Sold]);

        $this->assertFalse($this->service->canTransition($project, ProjectStatus::Draft));
        $this->assertFalse($this->service->canTransition($project, ProjectStatus::Completed));
    }

    public function test_submit_for_moderation_updates_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $result = $this->service->submitForModeration($project);

        $this->assertTrue($result);
        $project->refresh();
        $this->assertEquals(ProjectStatus::Moderation, $project->status);
        $this->assertEquals(ModerationStatus::Pending, $project->status_moderation);
    }

    public function test_submit_for_moderation_fails_from_wrong_status(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Announced]);

        $result = $this->service->submitForModeration($project);

        $this->assertFalse($result);
    }

    public function test_approve_updates_status_and_sets_announced_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Moderation,
        ]);

        $result = $this->service->approve($project);

        $this->assertTrue($result);
        $project->refresh();
        $this->assertEquals(ProjectStatus::Announced, $project->status);
        $this->assertEquals(ModerationStatus::Approved, $project->status_moderation);
        $this->assertNotNull($project->announced_at);
    }

    public function test_reject_updates_status_with_reason(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Moderation,
        ]);

        $reason = 'Недостатньо інформації про проєкт.';
        $result = $this->service->reject($project, $reason);

        $this->assertTrue($result);
        $project->refresh();
        $this->assertEquals(ProjectStatus::Rejected, $project->status);
        $this->assertEquals(ModerationStatus::Rejected, $project->status_moderation);
    }

    public function test_get_allowed_transitions_returns_correct_statuses(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Draft]);

        $allowed = $this->service->getAllowedTransitions($project);

        $this->assertCount(1, $allowed);
        $this->assertEquals(ProjectStatus::Moderation, $allowed[0]);
    }

    public function test_get_allowed_transitions_for_in_progress(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::InProgress]);

        $allowed = $this->service->getAllowedTransitions($project);

        $this->assertCount(3, $allowed);
        $this->assertContains(ProjectStatus::Completed, $allowed);
        $this->assertContains(ProjectStatus::Paused, $allowed);
        $this->assertContains(ProjectStatus::Sold, $allowed);
    }
}
