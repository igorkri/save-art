<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;

class ProjectStagesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Список етапів проекту
    // ==========================================

    public function test_can_get_project_stages(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        ProjectStage::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$project->id}/stages");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ==========================================
    // Створення етапу
    // ==========================================

    public function test_can_create_stage(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $data = [
            'title' => ['uk' => 'Етап 1', 'en' => 'Stage 1'],
            'description' => ['uk' => 'Опис етапу', 'en' => 'Stage description'],
            'budget_planned' => 5000,
            'days_planned' => 14,
            'order' => 1,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/stages", $data);

        $response->assertCreated()
            ->assertJsonPath('data.budget_planned', 5000);

        $this->assertDatabaseHas('project_stages', [
            'project_id' => $project->id,
            'budget_planned' => 5000,
        ]);
    }

    public function test_cannot_create_stage_without_required_fields(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/stages", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    // ==========================================
    // Оновлення етапу
    // ==========================================

    public function test_can_update_stage(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $stage = ProjectStage::factory()->create([
            'project_id' => $project->id,
            'budget_planned' => 5000,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->id}/stages/{$stage->id}", [
                'title' => ['uk' => 'Оновлений етап', 'en' => 'Updated stage'],
                'budget_planned' => 7000,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('project_stages', [
            'id' => $stage->id,
            'budget_planned' => 7000,
        ]);
    }

    // ==========================================
    // Видалення етапу
    // ==========================================

    public function test_can_delete_stage(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $stage = ProjectStage::factory()->create([
            'project_id' => $project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$project->id}/stages/{$stage->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('project_stages', [
            'id' => $stage->id,
        ]);
    }

    // ==========================================
    // Захист
    // ==========================================

    public function test_cannot_manage_stages_for_other_user_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/stages", [
                'title' => ['uk' => 'Етап', 'en' => 'Stage'],
                'budget_planned' => 5000,
            ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $project = Project::factory()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/my/projects/{$project->id}/stages");

        $response->assertUnauthorized();
    }
}
