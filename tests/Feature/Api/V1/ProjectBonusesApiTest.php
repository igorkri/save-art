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
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);
    }

    // ==========================================
    // Список бонусів
    // ==========================================

    public function test_can_get_project_bonuses(): void
    {
        ProjectBonus::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$this->project->id}/bonuses");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ==========================================
    // Створення бонусу
    // ==========================================

    public function test_can_create_bonus(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/bonuses", [
                'title' => ['uk' => 'Бонус 1', 'en' => 'Bonus 1'],
                'description' => ['uk' => 'Опис бонусу', 'en' => 'Bonus description'],
                'min_amount' => 100,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.min_amount', 100);
    }

    public function test_bonus_requires_title_and_amount(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/bonuses", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'min_amount']);
    }

    // ==========================================
    // Оновлення бонусу
    // ==========================================

    public function test_can_update_bonus(): void
    {
        $bonus = ProjectBonus::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$this->project->id}/bonuses/{$bonus->id}", [
                'title' => ['uk' => 'Оновлений бонус', 'en' => 'Updated bonus'],
                'min_amount' => 200,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('project_bonuses', [
            'id' => $bonus->id,
            'min_amount' => 200,
        ]);
    }

    // ==========================================
    // Видалення бонусу
    // ==========================================

    public function test_can_delete_bonus(): void
    {
        $bonus = ProjectBonus::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$this->project->id}/bonuses/{$bonus->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('project_bonuses', [
            'id' => $bonus->id,
        ]);
    }

    // ==========================================
    // Захист
    // ==========================================

    public function test_cannot_access_other_user_project_bonuses(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$otherProject->id}/bonuses");

        $response->assertForbidden();
    }
}
