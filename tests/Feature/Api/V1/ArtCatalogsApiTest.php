<?php

namespace Tests\Feature\Api\V1;

use App\Models\ArtCatalog;
use App\Models\ArtCategory;

class ArtCatalogsApiTest extends ApiTestCase
{
    public function test_can_get_catalogs_list(): void
    {
        ArtCatalog::factory()->count(3)->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/catalogs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'image_url', 'likes_count'],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_catalogs_by_art_category(): void
    {
        $slug = 'test-visual-'.\Illuminate\Support\Str::random(6);
        $category = ArtCategory::create(['slug' => $slug, 'name' => ['uk' => 'Візуальне', 'en' => 'Visual']]);
        $other = ArtCategory::create(['slug' => 'test-music-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Музика', 'en' => 'Music']]);

        ArtCatalog::factory()->create(['art_category_id' => $category->id]);
        ArtCatalog::factory()->create(['art_category_id' => $other->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/catalogs?art_category={$slug}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_get_single_catalog(): void
    {
        $catalog = ArtCatalog::factory()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/catalogs/{$catalog->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $catalog->id);
    }

    public function test_returns_404_for_nonexistent_catalog(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/catalogs/999999');

        $response->assertNotFound();
    }
}
