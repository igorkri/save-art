<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\User;

class ProjectBonusesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Список бонусів проекту
    // ==========================================

    public function test_can_get_project_bonuses(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        ProjectBonus::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$project->id}/bonuses");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ==========================================
    // Створення бонусу
    // ==========================================

    public function test_can_create_bonus(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $data = [
            'title' => ['uk' => 'Бонус', 'en' => 'Bonus'],
            'description' => ['uk' => 'Опис бонусу', 'en' => 'Bonus description'],
            'min_donation' => 500,
            'quantity' => 100,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/bonuses", $data);

        $response->assertCreated()
            ->assertJsonPath('data.min_donation', 500);

        $this->assertDatabaseHas('project_bonuses', [
            'project_id' => $project->id,
            'min_donation' => 500,
        ]);
    }

    public function test_cannot_create_bonus_without_required_fields(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/bonuses", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'min_donation']);
    }

    // ==========================================
    // Оновлення бонусу
    // ==========================================

    public function test_can_update_bonus(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $bonus = ProjectBonus::factory()->create([
            'project_id' => $project->id,
            'min_donation' => 500,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->id}/bonuses/{$bonus->id}", [
                'title' => ['uk' => 'Оновлений бонус', 'en' => 'Updated bonus'],
                'min_donation' => 1000,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('project_bonuses', [
            'id' => $bonus->id,
            'min_donation' => 1000,
        ]);
    }

    // ==========================================
    // Видалення бонусу
    // ==========================================

    public function test_can_delete_bonus(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $bonus = ProjectBonus::factory()->create([
            'project_id' => $project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$project->id}/bonuses/{$bonus->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('project_bonuses', [
            'id' => $bonus->id,
        ]);
    }

    // ==========================================
    // Захист
    // ==========================================

    public function test_cannot_manage_bonuses_for_other_user_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/bonuses", [
                'title' => ['uk' => 'Бонус', 'en' => 'Bonus'],
                'min_donation' => 500,
            ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $project = Project::factory()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/my/projects/{$project->id}/bonuses");

        $response->assertUnauthorized();
    }
}
