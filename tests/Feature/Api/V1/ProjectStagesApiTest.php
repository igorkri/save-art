<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProjectStagesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::InProgress,
        ]);
    }

    // ==========================================
    // Список етапів
    // ==========================================

    public function test_can_get_project_stages(): void
    {
        ProjectStage::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$this->project->id}/stages");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ==========================================
    // Створення етапу
    // ==========================================

    public function test_can_create_stage(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages", [
                'title' => ['uk' => 'Етап 1', 'en' => 'Stage 1'],
                'description' => ['uk' => 'Опис етапу', 'en' => 'Stage description'],
                'budget' => 5000,
                'planned_days' => 30,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.budget', 5000);
    }

    public function test_stage_requires_title(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages", [
                'budget' => 5000,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    // ==========================================
    // Оновлення етапу
    // ==========================================

    public function test_can_update_stage(): void
    {
        $stage = ProjectStage::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$this->project->id}/stages/{$stage->id}", [
                'title' => ['uk' => 'Оновлений', 'en' => 'Updated'],
                'budget' => 10000,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('project_stages', [
            'id' => $stage->id,
            'budget' => 10000,
        ]);
    }

    // ==========================================
    // Видалення етапу
    // ==========================================

    public function test_can_delete_stage(): void
    {
        $stage = ProjectStage::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$this->project->id}/stages/{$stage->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('project_stages', [
            'id' => $stage->id,
        ]);
    }

    // ==========================================
    // Старт етапу
    // ==========================================

    public function test_can_start_stage(): void
    {
        $stage = ProjectStage::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'planned',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$stage->id}/start");

        $response->assertOk();
    }

    // ==========================================
    // Завершення етапу
    // ==========================================

    public function test_can_complete_stage(): void
    {
        $stage = ProjectStage::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$stage->id}/complete");

        $response->assertOk();
    }

    // ==========================================
    // Документи етапу
    // ==========================================

    public function test_can_upload_stage_documents(): void
    {
        Storage::fake('public');

        $stage = ProjectStage::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$stage->id}/documents", [
                'documents' => [$file],
            ]);

        $response->assertOk();
    }

    public function test_can_delete_stage_document(): void
    {
        $stage = ProjectStage::factory()->create([
            'project_id' => $this->project->id,
            'documents' => ['path/to/document.pdf'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$this->project->id}/stages/{$stage->id}/documents/0");

        $response->assertOk();
    }

    // ==========================================
    // Захист
    // ==========================================

    public function test_cannot_access_other_user_project_stages(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$otherProject->id}/stages");

        $response->assertForbidden();
    }
}
