<?php

namespace Tests\Feature;

use App\Models\ProjectDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectDraftControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Вимикаємо перевірку API key для тестів
        config(['services.api_key' => '']);
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_drafts(): void
    {
        $this->getJson('/api/v1/my/drafts')
            ->assertUnauthorized();
    }

    public function test_user_can_list_their_drafts(): void
    {
        $drafts = ProjectDraft::factory()->count(3)->create(['user_id' => $this->user->id]);
        ProjectDraft::factory()->count(2)->create(); // Other user's drafts

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/my/drafts');

        $response->assertOk()
            ->assertJsonStructure([
                'result',
                'data' => [
                    'drafts' => [
                        '*' => ['id', 'project_id', 'status', 'data', 'created_at', 'updated_at'],
                    ],
                    'count',
                ],
            ])
            ->assertJsonPath('data.count', 3);
    }

    public function test_deleted_drafts_are_not_listed(): void
    {
        ProjectDraft::factory()->create(['user_id' => $this->user->id]);
        ProjectDraft::factory()->deleted()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/my/drafts');

        $response->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_user_can_create_draft(): void
    {
        $data = [
            'data' => [
                'title' => ['uk' => 'Тестовий проєкт', 'en' => 'Test project'],
                'budget_goal' => 50000,
                'currency' => 'UAH',
            ],
        ];

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/my/drafts', $data);

        $response->assertCreated()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.draft.status', ProjectDraft::STATUS_NEW)
            ->assertJsonPath('data.draft.data.title.uk', 'Тестовий проєкт');

        $this->assertDatabaseHas('project_drafts', [
            'user_id' => $this->user->id,
            'status' => ProjectDraft::STATUS_NEW,
        ]);
    }

    public function test_create_draft_requires_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/my/drafts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['data']);
    }

    public function test_user_can_show_their_draft(): void
    {
        $draft = ProjectDraft::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/my/drafts/{$draft->id}");

        $response->assertOk()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.draft.id', $draft->id);
    }

    public function test_user_cannot_show_other_users_draft(): void
    {
        $otherDraft = ProjectDraft::factory()->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/my/drafts/{$otherDraft->id}");

        $response->assertNotFound();
    }

    public function test_user_can_update_their_draft(): void
    {
        $draft = ProjectDraft::factory()->create(['user_id' => $this->user->id]);

        $newData = [
            'data' => [
                'title' => ['uk' => 'Оновлений проєкт', 'en' => 'Updated project'],
                'budget_goal' => 100000,
            ],
        ];

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/my/drafts/{$draft->id}", $newData);

        $response->assertOk()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.draft.data.title.uk', 'Оновлений проєкт');

        $this->assertDatabaseHas('project_drafts', [
            'id' => $draft->id,
        ]);
    }

    public function test_user_cannot_update_exported_draft(): void
    {
        $draft = ProjectDraft::factory()->exported()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/my/drafts/{$draft->id}", [
            'data' => ['title' => ['uk' => 'Test']],
        ]);

        $response->assertNotFound();
    }

    public function test_user_can_delete_their_draft(): void
    {
        $draft = ProjectDraft::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/my/drafts/{$draft->id}");

        $response->assertOk()
            ->assertJsonPath('result', true);

        $this->assertDatabaseHas('project_drafts', [
            'id' => $draft->id,
            'status' => ProjectDraft::STATUS_DELETED,
        ]);
    }

    public function test_user_can_archive_their_draft(): void
    {
        $draft = ProjectDraft::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/my/drafts/{$draft->id}/archive");

        $response->assertOk()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.draft.status', ProjectDraft::STATUS_ARCHIVED);

        $this->assertDatabaseHas('project_drafts', [
            'id' => $draft->id,
            'status' => ProjectDraft::STATUS_ARCHIVED,
        ]);
    }

    public function test_user_cannot_archive_already_archived_draft(): void
    {
        $draft = ProjectDraft::factory()->archived()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/my/drafts/{$draft->id}/archive");

        $response->assertNotFound();
    }
}
