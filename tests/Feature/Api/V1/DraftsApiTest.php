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

    // ==========================================
    // Тест повного формату чернетки (згідно специфікації)
    // ==========================================

    public function test_can_create_full_draft_with_all_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => '1771078053865',
                'user_type' => 'personal',
                'title' => ['uk' => 'Назва українською', 'en' => 'Title in English'],
                'short_description' => ['uk' => 'Короткий опис...', 'en' => 'Short description...'],
                'tags' => ['uk' => 'тег1, тег2', 'en' => 'tag1, tag2'],
                'art_category' => 'fine_art',
                'art_subcategory' => 'painting',
                'currency' => 'UAH',
                'budget_goal' => 75000,
                'estimated_days' => 90,
                'budget_items' => [
                    ['name' => ['uk' => 'Стаття', 'en' => 'Item'], 'amount' => 10000],
                ],
                'characteristics' => [
                    ['name' => ['uk' => 'Назва', 'en' => 'Name'], 'value' => ['uk' => 'Значення', 'en' => 'Value']],
                ],
                'content_blocks' => [
                    ['type' => 'heading', 'heading_level' => 'h2', 'heading_text' => ['uk' => 'Заголовок', 'en' => 'Heading']],
                    ['type' => 'paragraph', 'paragraph_text' => ['uk' => 'Текст параграфу', 'en' => 'Paragraph text']],
                ],
                'stages' => [
                    [
                        'title' => ['uk' => 'Етап 1', 'en' => 'Stage 1'],
                        'description' => ['uk' => 'Опис етапу', 'en' => 'Stage description'],
                        'days_planned' => 14,
                        'budget_planned' => 5000,
                    ],
                ],
                'bonuses' => [
                    [
                        'title' => ['uk' => 'Бонус 1', 'en' => 'Bonus 1'],
                        'description' => ['uk' => 'Опис бонусу', 'en' => 'Bonus description'],
                        'min_donation' => 100,
                        'quantity' => null,
                    ],
                ],
                'cover' => '',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.draft.local_id', '1771078053865')
            ->assertJsonPath('data.draft.user_type', 'personal')
            ->assertJsonPath('data.draft.title.uk', 'Назва українською')
            ->assertJsonPath('data.draft.tags.uk', 'тег1, тег2')
            ->assertJsonPath('data.draft.budget_goal', 75000)
            ->assertJsonPath('data.draft.estimated_days', 90)
            ->assertJsonPath('data.draft.currency', 'UAH')
            ->assertJsonPath('data.draft.art_category', 'fine_art');

        // Перевіряємо повний формат через GET
        $draftId = $response->json('data.draft.id');

        $getResponse = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/drafts/{$draftId}");

        $getResponse->assertOk()
            ->assertJsonPath('data.draft.budget_items.0.name.uk', 'Стаття')
            ->assertJsonPath('data.draft.characteristics.0.name.uk', 'Назва')
            ->assertJsonPath('data.draft.content_blocks.0.type', 'heading')
            ->assertJsonPath('data.draft.stages.0.title.uk', 'Етап 1')
            ->assertJsonPath('data.draft.stages.0.days_planned', 14)
            ->assertJsonPath('data.draft.stages.0.budget_planned', 5000)
            ->assertJsonPath('data.draft.bonuses.0.title.uk', 'Бонус 1')
            ->assertJsonPath('data.draft.bonuses.0.min_donation', 100);
    }

    public function test_can_update_draft_by_local_id(): void
    {
        // Створюємо чернетку
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => 'update-by-local-id-test',
                'title' => ['uk' => 'Оригінальна назва'],
            ]);

        $createResponse->assertCreated();

        // Повторний POST з тим самим local_id має оновити чернетку
        $updateResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => 'update-by-local-id-test',
                'title' => ['uk' => 'Оновлена назва'],
                'budget_goal' => 50000,
            ]);

        $updateResponse->assertCreated()
            ->assertJsonPath('data.draft.title.uk', 'Оновлена назва')
            ->assertJsonPath('data.draft.budget_goal', 50000);

        // Переконуємося, що це та сама чернетка
        $this->assertEquals(
            $createResponse->json('data.draft.id'),
            $updateResponse->json('data.draft.id')
        );
    }

    public function test_can_create_draft_with_fine_arts_normalization(): void
    {
        // Фронтенд може надсилати 'fine_arts' замість 'fine_art'
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/drafts', [
                'local_id' => 'fine-arts-test',
                'title' => ['uk' => 'Тест нормалізації'],
                'art_category' => 'fine_arts', // з 's' на кінці
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.draft.art_category', 'fine_art'); // нормалізовано без 's'
    }
}
