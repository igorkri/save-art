<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MyProjectsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Список моїх проектів
    // ==========================================

    public function test_can_get_my_projects(): void
    {
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Чужий проект
        Project::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/projects');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_my_projects_by_status(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);
        Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/projects?status=draft');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ==========================================
    // Створення проекту
    // ==========================================

    public function test_can_create_project(): void
    {
        $data = [
            'user_type' => 'personal',
            'title' => ['uk' => 'Мій проект', 'en' => 'My project'],
            'short_description' => ['uk' => 'Короткий опис', 'en' => 'Short description'],
            'art_category' => 'music',
            'budget_goal' => 10000,
            'currency' => 'UAH',
            'estimated_days' => 30,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/projects', $data);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'budget_goal' => 10000,
        ]);
    }

    public function test_cannot_create_project_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/projects', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'art_category', 'budget_goal']);
    }

    public function test_currency_is_forced_to_usd_on_create_regardless_of_input(): void
    {
        $data = [
            'user_type' => 'personal',
            'title' => ['uk' => 'Мій проект', 'en' => 'My project'],
            'short_description' => ['uk' => 'Короткий опис', 'en' => 'Short description'],
            'art_category' => 'music',
            'budget_goal' => 10000,
            'currency' => 'EUR',
            'estimated_days' => 30,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/projects', $data);

        $response->assertCreated()
            ->assertJsonPath('data.currency', 'USD');

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'currency' => 'USD',
        ]);
    }

    public function test_currency_is_forced_to_usd_on_update_regardless_of_input(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
            'currency' => 'USD',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Назва', 'en' => 'Title'],
                'short_description' => ['uk' => 'Опис', 'en' => 'Description'],
                'art_category' => 'fine_art',
                'budget_goal' => 20000,
                'currency' => 'UAH',
                'estimated_days' => 60,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.currency', 'USD');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'currency' => 'USD',
        ]);
    }

    // ==========================================
    // Перегляд проекту
    // ==========================================

    public function test_can_get_my_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_cannot_get_other_user_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$project->id}");

        $response->assertForbidden();
    }

    // ==========================================
    // Оновлення проекту
    // ==========================================

    public function test_can_update_my_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->id}", [
                'title' => ['uk' => 'Оновлена назва', 'en' => 'Updated title'],
                'short_description' => ['uk' => 'Опис', 'en' => 'Description'],
                'art_category' => 'fine_art',
                'budget_goal' => 20000,
                'currency' => 'UAH',
                'estimated_days' => 60,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'budget_goal' => 20000,
        ]);
    }

    public function test_can_update_project_pending_moderation_and_it_reverts_to_draft(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Оновлена назва', 'en' => 'Updated title'],
                'short_description' => ['uk' => 'Опис', 'en' => 'Description'],
                'art_category' => 'fine_art',
                'budget_goal' => 20000,
                'currency' => 'UAH',
                'estimated_days' => 60,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Draft->value,
        ]);
    }

    public function test_cannot_update_project_being_actively_reviewed(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Processing,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Спроба редагування', 'en' => 'Edit attempt'],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Moderation->value,
            'status_moderation' => ModerationStatus::Processing->value,
        ]);

        $response->assertJsonFragment([
            'error_code' => 'project_under_review',
        ]);
    }

    public function test_cannot_fully_update_announced_project_and_gets_a_helpful_message(): void
    {
        $project = Project::factory()->announced()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Спроба редагування', 'en' => 'Edit attempt'],
            ]);

        $response->assertForbidden();
        $response->assertJsonFragment([
            'error_code' => 'project_partially_editable',
            'status' => 'announced',
        ]);
    }

    public function test_can_partially_update_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        // PUT на Laravel вимагає всі обов'язкові поля, тому використовуємо повний набір
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->id}", [
                'title' => $project->title,
                'art_category' => $project->art_category->value,
                'budget_goal' => 15000,
                'currency' => $project->currency->value,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'budget_goal' => 15000,
        ]);
    }

    // ==========================================
    // Видалення проекту
    // ==========================================

    public function test_can_delete_draft_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$project->id}");

        // Контролер повертає 200 з JSON, не 204
        $response->assertOk();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }

    public function test_cannot_delete_active_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$project->id}");

        // Контролер повертає 422 з повідомленням
        $response->assertUnprocessable();
    }

    // ==========================================
    // Подання на модерацію
    // ==========================================

    public function test_can_submit_project_for_moderation(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/submit");

        $response->assertOk();
    }

    // ==========================================
    // Завершення проекту
    // ==========================================

    public function test_can_complete_active_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::InProgress,
            'final_result' => ['type' => 'image', 'file' => ['path' => 'test.jpg']],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/complete");

        $response->assertOk();
    }

    // ==========================================
    // Завантаження фінального результату
    // ==========================================

    public function test_can_upload_final_result(): void
    {
        Storage::fake('public');

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $file = UploadedFile::fake()->image('result.jpg', 800, 600);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->id}/final-result/upload", [
                'type' => 'image',
                'files' => [$file],
            ]);

        $response->assertOk();
    }

    // ==========================================
    // Захист
    // ==========================================

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/my/projects');

        $response->assertUnauthorized();
    }
}
