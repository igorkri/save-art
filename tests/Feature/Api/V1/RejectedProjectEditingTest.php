<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

/**
 * Rejected — фінальний стан (docs/project-lifecycle-flow.md): без повторної подачі на
 * модерацію, єдина дозволена дія — видалення. Ці тести фіксують, що бекенд не дозволяє
 * редагувати відхилений проєкт (навіть якщо хтось звернеться до API напряму, в обхід UI).
 */
class RejectedProjectEditingTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_cannot_fully_update_rejected_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Rejected,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Спроба редагування'],
            ]);

        $response->assertForbidden();
        $response->assertJsonFragment(['error_code' => 'project_not_editable']);
    }

    public function test_cannot_add_bonus_to_rejected_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Rejected,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->slug}/bonuses", [
                'title' => ['uk' => 'Бонус'],
                'min_donation' => 100,
            ]);

        $response->assertStatus(422)->assertJsonFragment(['message' => 'Проєкт не можна редагувати']);
    }

    public function test_cannot_add_stage_to_rejected_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Rejected,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->slug}/stages", [
                'title' => ['uk' => 'Етап'],
                'budget_planned' => 100,
            ]);

        $response->assertStatus(422)->assertJsonFragment(['message' => 'Проєкт не можна редагувати']);
    }

    public function test_can_still_delete_rejected_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Rejected,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$project->slug}");

        $response->assertOk();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
