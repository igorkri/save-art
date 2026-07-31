<?php

namespace Tests\Feature\Api\V1;

use App\Models\ArtCatalog;
use App\Models\ArtCategory;
use App\Models\User;

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

    public function test_can_get_catalogs_list_via_art_ua_info_route(): void
    {
        ArtCatalog::factory()->count(2)->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/art-ua-info/catalogs');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_filter_catalogs_by_multiple_subcategories(): void
    {
        $root = ArtCategory::create(['slug' => 'test-root-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Корінь', 'en' => 'Root']]);
        $subA = ArtCategory::create(['slug' => 'test-sub-a-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'А', 'en' => 'A'], 'parent_id' => $root->id]);
        $subB = ArtCategory::create(['slug' => 'test-sub-b-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Б', 'en' => 'B'], 'parent_id' => $root->id]);
        $other = ArtCategory::create(['slug' => 'test-other-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Інше', 'en' => 'Other']]);

        ArtCatalog::factory()->create(['art_category_id' => $subA->id]);
        ArtCatalog::factory()->create(['art_category_id' => $subB->id]);
        ArtCatalog::factory()->create(['art_category_id' => $other->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/art-ua-info/catalogs?art_subcategory={$subA->slug},{$subB->slug}");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_search_catalogs_by_title(): void
    {
        ArtCatalog::factory()->create(['title' => ['uk' => 'унікальна назва каталогу', 'en' => 'unique catalog title']]);
        ArtCatalog::factory()->create(['title' => ['uk' => 'інший каталог', 'en' => 'another catalog']]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/art-ua-info/catalogs?search=унікальна');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_sort_catalogs_by_likes(): void
    {
        $low = ArtCatalog::factory()->create(['likes_count' => 3]);
        $high = ArtCatalog::factory()->create(['likes_count' => 10]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/art-ua-info/catalogs?sort_by=likes');

        $response->assertOk()->assertJsonPath('data.0.id', $high->id);
    }

    public function test_can_filter_by_childless_root_category_slug(): void
    {
        $childless = ArtCategory::create(['slug' => 'test-childless-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Музика', 'en' => 'Music']]);
        $other = ArtCategory::create(['slug' => 'test-other-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Інше', 'en' => 'Other']]);

        ArtCatalog::factory()->create(['art_category_id' => $childless->id]);
        ArtCatalog::factory()->create(['art_category_id' => $other->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/art-ua-info/catalogs?art_subcategory={$childless->slug}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_response_includes_category_counts_for_filters(): void
    {
        $root = ArtCategory::create(['slug' => 'test-root-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Корінь', 'en' => 'Root']]);
        $subA = ArtCategory::create(['slug' => 'test-sub-a-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'А', 'en' => 'A'], 'parent_id' => $root->id]);
        $subB = ArtCategory::create(['slug' => 'test-sub-b-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Б', 'en' => 'B'], 'parent_id' => $root->id]);

        ArtCatalog::factory()->count(2)->create(['art_category_id' => $subA->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/art-ua-info/catalogs');

        $response->assertOk();

        $categories = collect($response->json('filters.categories'));
        $rootData = $categories->firstWhere('slug', $root->slug);

        $this->assertNotNull($rootData);
        $this->assertSame(2, $rootData['catalogs_count']);

        $subcategories = collect($rootData['subcategories']);
        $this->assertSame(2, $subcategories->firstWhere('slug', $subA->slug)['catalogs_count']);
        $this->assertSame(0, $subcategories->firstWhere('slug', $subB->slug)['catalogs_count']);
    }

    public function test_catalog_includes_root_and_child_category_names(): void
    {
        $root = ArtCategory::create(['slug' => 'test-root-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Візуальне мистецтво', 'en' => 'Visual art']]);
        $sub = ArtCategory::create(['slug' => 'test-sub-'.\Illuminate\Support\Str::random(6), 'name' => ['uk' => 'Доповнена реальність', 'en' => 'Augmented reality'], 'parent_id' => $root->id]);
        $catalog = ArtCatalog::factory()->create(['art_category_id' => $sub->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/art-ua-info/catalogs/{$catalog->id}?language=uk");

        $response->assertOk()
            ->assertJsonPath('data.art_category.name', 'Доповнена реальність')
            ->assertJsonPath('data.art_category.root_name', 'Візуальне мистецтво');
    }

    public function test_can_filter_catalogs_by_author_slug(): void
    {
        $author = User::factory()->create(['slug' => 'test-author-'.\Illuminate\Support\Str::random(6)]);
        $other = User::factory()->create();

        ArtCatalog::factory()->create(['user_id' => $author->id]);
        ArtCatalog::factory()->create(['user_id' => $other->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/art-ua-info/catalogs?author_slug={$author->slug}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_like_and_unlike_catalog_via_art_ua_info_route(): void
    {
        $user = User::factory()->create();
        $catalog = ArtCatalog::factory()->create(['likes_count' => 0]);

        $likeResponse = $this->authPost($user, "/api/v1/art-ua-info/catalogs/{$catalog->id}/like");
        $likeResponse->assertOk()->assertJsonPath('is_liked', true)->assertJsonPath('likes_count', 1);

        $showResponse = $this->withHeaders($this->authHeaders($user))
            ->getJson("/api/v1/art-ua-info/catalogs/{$catalog->id}");
        $showResponse->assertOk()->assertJsonPath('data.is_liked', true);

        $unlikeResponse = $this->authDelete($user, "/api/v1/art-ua-info/catalogs/{$catalog->id}/like");
        $unlikeResponse->assertOk()->assertJsonPath('is_liked', false)->assertJsonPath('likes_count', 0);
    }
}
