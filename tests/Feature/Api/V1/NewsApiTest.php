<?php

namespace Tests\Feature\Api\V1;

use App\Enums\NewsCategory;
use App\Models\News;

class NewsApiTest extends ApiTestCase
{
    // ==========================================
    // Список новин
    // ==========================================

    public function test_can_get_news_list(): void
    {
        News::factory()->count(5)->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'category',
                        'category_label',
                        'title',
                        'date',
                        'published_at',
                        'main_image_url',
                    ],
                ],
                'meta',
                'links',
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_news_list_does_not_include_text_blocks(): void
    {
        News::factory()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news');

        $response->assertOk();
        $this->assertArrayNotHasKey('text_blocks', $response->json('data')[0]);
    }

    public function test_inactive_news_not_shown_in_list(): void
    {
        News::factory()->create(['is_active' => true]);
        News::factory()->inactive()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_news_list_sorted_by_published_at_desc(): void
    {
        $older = News::factory()->create(['published_at' => now()->subDays(5)]);
        $newer = News::factory()->create(['published_at' => now()->subDay()]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news');

        $response->assertOk();
        $this->assertSame($newer->id, $response->json('data.0.id'));
        $this->assertSame($older->id, $response->json('data.1.id'));
    }

    public function test_can_filter_news_by_category(): void
    {
        News::factory()->news()->count(2)->create();
        News::factory()->event()->count(3)->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news?category=event');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $item) {
            $this->assertSame(NewsCategory::Event->value, $item['category']);
        }
    }

    public function test_can_get_news_list_localized(): void
    {
        News::factory()->create([
            'title' => 'Заголовок',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news?language=uk');

        $response->assertOk()
            ->assertJsonPath('language', 'uk')
            ->assertJsonPath('data.0.title', 'Заголовок');
    }

    // ==========================================
    // Одна новина за slug
    // ==========================================

    public function test_can_get_news_item_by_slug(): void
    {
        $news = News::factory()->create(['slug' => 'test-news-item']);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news/test-news-item');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'slug',
                    'category',
                    'category_label',
                    'title',
                    'date',
                    'published_at',
                    'main_image_url',
                    'text_blocks',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $news->id);
    }

    public function test_news_item_text_blocks_paragraphs(): void
    {
        News::factory()->create([
            'slug' => 'localized-news',
            'text_blocks' => [
                [
                    'paragraphs' => ['Перший абзац'],
                    'image' => null,
                ],
            ],
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news/localized-news?language=uk');

        $response->assertOk()
            ->assertJsonPath('data.text_blocks.0.paragraphs.0', 'Перший абзац');
    }

    public function test_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news/nonexistent-slug');

        $response->assertNotFound();
    }

    public function test_inactive_news_returns_404(): void
    {
        News::factory()->inactive()->create(['slug' => 'inactive-news']);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/news/inactive-news');

        $response->assertNotFound();
    }
}
