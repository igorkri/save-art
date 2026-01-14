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
        // Створюємо чернетку через API
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => 'draft-1',
                'title' => ['uk' => 'Draft 1'],
            ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/drafts');

        $response->assertOk()
            ->assertJsonStructure([
                'result',
                'data' => [
                    'drafts' => [
                        '*' => [
                            'id',
                            'local_id',
                            'title',
                        ],
                    ],
                    'count',
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
                'local_id' => 'unique-draft-id',
                'title' => ['uk' => 'Чернетка', 'en' => 'Draft'],
                'short_description' => ['uk' => 'Опис', 'en' => 'Description'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.draft.local_id', 'unique-draft-id');
    }

    public function test_can_create_draft_without_local_id(): void
    {
        // local_id є опціональним, контролер створить чернетку без нього
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'title' => ['uk' => 'Нова чернетка'],
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'result',
                'message',
                'data' => [
                    'draft' => [
                        'id',
                        'title',
                    ],
                ],
            ]);
    }

    // ==========================================
    // Перегляд чернетки
    // ==========================================

    public function test_can_get_draft(): void
    {
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => 'draft-to-view',
                'title' => ['uk' => 'View this'],
            ]);

        $draftId = $createResponse->json('data.draft.id');

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/drafts/{$draftId}");

        $response->assertOk()
            ->assertJsonPath('data.draft.local_id', 'draft-to-view');
    }

    // ==========================================
    // Оновлення чернетки
    // ==========================================

    public function test_can_update_draft(): void
    {
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => 'draft-to-update',
                'title' => ['uk' => 'Original'],
            ]);

        $draftId = $createResponse->json('data.draft.id');

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/drafts/{$draftId}", [
                'title' => ['uk' => 'Updated'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.draft.title.uk', 'Updated');
    }

    // ==========================================
    // Видалення чернетки
    // ==========================================

    public function test_can_delete_draft(): void
    {
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => 'draft-to-delete',
                'title' => ['uk' => 'Delete me'],
            ]);

        $draftId = $createResponse->json('data.draft.id');

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/drafts/{$draftId}");

        // Контролер повертає 200 з JSON, а не 204
        $response->assertOk()
            ->assertJsonPath('result', true);
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
                        'local_id' => 'sync-draft-1',
                        'updated_at' => now()->toIso8601String(),
                        'data' => ['title' => ['uk' => 'Synced 1']],
                    ],
                    [
                        'local_id' => 'sync-draft-2',
                        'updated_at' => now()->toIso8601String(),
                        'data' => ['title' => ['uk' => 'Synced 2']],
                    ],
                ],
            ]);

        $response->assertOk();
    }
}
