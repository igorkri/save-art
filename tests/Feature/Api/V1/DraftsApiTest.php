<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;

class DraftsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Список чернеток
    // ==========================================

    public function test_can_get_drafts(): void
    {
        // Створюємо чернетки через API
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'client_id' => 'draft-1',
                'data' => ['title' => 'Draft 1'],
            ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/drafts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'client_id',
                        'data',
                    ],
                ],
            ]);
    }

    // ==========================================
    // Створення чернетки
    // ==========================================

    public function test_can_create_draft(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'client_id' => 'unique-draft-id',
                'data' => [
                    'title' => ['uk' => 'Чернетка', 'en' => 'Draft'],
                    'description' => 'Some description',
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.client_id', 'unique-draft-id');
    }

    public function test_draft_requires_client_id(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'data' => ['title' => 'Draft'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['client_id']);
    }

    // ==========================================
    // Перегляд чернетки
    // ==========================================

    public function test_can_get_draft(): void
    {
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'client_id' => 'draft-to-view',
                'data' => ['title' => 'View this'],
            ]);

        $draftId = $createResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/drafts/{$draftId}");

        $response->assertOk()
            ->assertJsonPath('data.client_id', 'draft-to-view');
    }

    // ==========================================
    // Оновлення чернетки
    // ==========================================

    public function test_can_update_draft(): void
    {
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'client_id' => 'draft-to-update',
                'data' => ['title' => 'Original'],
            ]);

        $draftId = $createResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/drafts/{$draftId}", [
                'data' => ['title' => 'Updated'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.data.title', 'Updated');
    }

    // ==========================================
    // Видалення чернетки
    // ==========================================

    public function test_can_delete_draft(): void
    {
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'client_id' => 'draft-to-delete',
                'data' => ['title' => 'Delete me'],
            ]);

        $draftId = $createResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/drafts/{$draftId}");

        $response->assertNoContent();
    }

    // ==========================================
    // Синхронізація чернеток
    // ==========================================

    public function test_can_sync_drafts(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts/sync', [
                'drafts' => [
                    [
                        'client_id' => 'sync-draft-1',
                        'data' => ['title' => 'Synced 1'],
                        'updated_at' => now()->toIso8601String(),
                    ],
                    [
                        'client_id' => 'sync-draft-2',
                        'data' => ['title' => 'Synced 2'],
                        'updated_at' => now()->toIso8601String(),
                    ],
                ],
            ]);

        $response->assertOk();
    }
}
