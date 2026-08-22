<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;

class ModerationControllerTest extends ApiTestCase
{
    private function pendingProject(): Project
    {
        return Project::factory()->create([
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);
    }

    public function test_regular_user_cannot_start_review(): void
    {
        $user = User::factory()->create();
        $project = $this->pendingProject();

        $response = $this->authPost($user, "/api/v1/moderation/projects/{$project->slug}/start-review");

        $response->assertForbidden();
    }

    public function test_project_author_cannot_moderate_own_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);

        $response = $this->authPost($project->user, "/api/v1/moderation/projects/{$project->slug}/start-review");

        $response->assertForbidden();
    }

    public function test_moderator_can_start_review(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/start-review");

        $response->assertOk()
            ->assertJsonPath('status_moderation', ModerationStatus::Processing->value);

        $this->assertSame(ModerationStatus::Processing, $project->refresh()->status_moderation);
    }

    public function test_approve_fails_before_start_review(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/approve");

        $response->assertStatus(422);
        $this->assertSame(ProjectStatus::Moderation, $project->refresh()->status);
    }

    public function test_moderator_can_approve_after_start_review(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/start-review")->assertOk();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/approve");

        $response->assertOk()
            ->assertJsonPath('status', ProjectStatus::Announced->value);

        $project->refresh();
        $this->assertSame(ProjectStatus::Announced, $project->status);
        $this->assertSame(ModerationStatus::Approved, $project->status_moderation);
    }

    public function test_reject_requires_reason(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/start-review")->assertOk();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/reject");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_moderator_can_reject_with_reason_after_start_review(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/start-review")->assertOk();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/reject", [
            'reason' => 'Недостатньо інформації про проєкт',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', ProjectStatus::Rejected->value);

        $project->refresh();
        $this->assertSame(ProjectStatus::Rejected, $project->status);
        $this->assertSame('Недостатньо інформації про проєкт', $project->rejection_reason);
    }

    public function test_return_for_revision_requires_comment(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/start-review")->assertOk();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/return-for-revision");

        $response->assertStatus(422)->assertJsonValidationErrors('comment');
    }

    public function test_moderator_can_return_project_for_revision(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/start-review")->assertOk();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/return-for-revision", [
            'comment' => 'Додайте більше деталей про проєкт.',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', ProjectStatus::Draft->value);

        $project->refresh();
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertSame(ModerationStatus::Pending, $project->status_moderation);
        $this->assertSame('Додайте більше деталей про проєкт.', $project->moderation_comment);
    }

    public function test_regular_user_cannot_return_project_for_revision(): void
    {
        $user = User::factory()->create();
        $project = $this->pendingProject();

        $response = $this->authPost($user, "/api/v1/moderation/projects/{$project->slug}/return-for-revision", [
            'comment' => 'Test',
        ]);

        $response->assertForbidden();
    }

    public function test_moderator_can_view_pending_project_on_public_endpoint(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $response = $this->authGet($moderator, "/api/v1/projects/{$project->slug}");

        $response->assertOk()->assertJsonPath('data.id', $project->id);
    }

    public function test_regular_user_cannot_view_pending_project_on_public_endpoint(): void
    {
        $user = User::factory()->create();
        $project = $this->pendingProject();

        $response = $this->authGet($user, "/api/v1/projects/{$project->slug}");

        $response->assertNotFound();
    }

    public function test_moderator_can_message_project_author_regardless_of_moderation_state(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/message", [
            'subject' => 'Щодо вашого проєкту',
            'content' => 'Будь ласка, додайте детальніший опис.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.content', 'Будь ласка, додайте детальніший опис.');

        $this->assertDatabaseHas('messages', [
            'user_id' => $project->user_id,
            'admin_id' => $moderator->id,
            'project_id' => $project->id,
            'subject' => 'Щодо вашого проєкту',
            'content' => 'Будь ласка, додайте детальніший опис.',
            'direction' => Message::DIRECTION_ADMIN_TO_USER,
        ]);
    }

    public function test_message_requires_content(): void
    {
        $moderator = User::factory()->admin()->create();
        $project = $this->pendingProject();

        $response = $this->authPost($moderator, "/api/v1/moderation/projects/{$project->slug}/message", [
            'subject' => 'Щодо вашого проєкту',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('content');
    }

    public function test_regular_user_cannot_message_project_author(): void
    {
        $user = User::factory()->create();
        $project = $this->pendingProject();

        $response = $this->authPost($user, "/api/v1/moderation/projects/{$project->slug}/message", [
            'content' => 'Test',
        ]);

        $response->assertForbidden();
    }
}
