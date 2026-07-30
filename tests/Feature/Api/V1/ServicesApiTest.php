<?php

namespace Tests\Feature\Api\V1;

use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;

class ServicesApiTest extends ApiTestCase
{
    public function test_can_get_services_list(): void
    {
        Service::factory()->count(2)->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['*' => ['id', 'slug', 'title', 'performer_type', 'performer']]])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_services_by_performer_type(): void
    {
        $team = Team::factory()->create();
        Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
        ]);
        Service::factory()->create([
            'serviceable_type' => User::class,
            'serviceable_id' => User::factory(),
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services?performer_type=team');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.performer_type', 'team');
    }

    public function test_can_get_single_service(): void
    {
        $service = Service::factory()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/services/{$service->slug}");

        $response->assertOk()->assertJsonPath('data.slug', $service->slug);
    }

    public function test_returns_404_for_nonexistent_service(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services/nonexistent-slug');

        $response->assertNotFound();
    }

    public function test_can_filter_services_by_subcategory(): void
    {
        $root = ArtCategory::factory()->create(['parent_id' => null]);
        $child = ArtCategory::factory()->create(['parent_id' => $root->id]);
        $otherChild = ArtCategory::factory()->create(['parent_id' => $root->id]);

        Service::factory()->create(['art_category_id' => $child->id]);
        Service::factory()->create(['art_category_id' => $otherChild->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/services?art_subcategory={$child->slug}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_filter_services_by_price_range(): void
    {
        Service::factory()->create(['price' => 100]);
        Service::factory()->create(['price' => 500]);
        Service::factory()->create(['price' => 1000]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services?price_min=200&price_max=800');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', 500);
    }

    public function test_can_filter_services_by_currency(): void
    {
        Service::factory()->create(['currency' => 'USD']);
        Service::factory()->create(['currency' => 'UAH']);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services?currency=USD');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.currency', 'USD');
    }

    public function test_can_filter_services_by_performer_city(): void
    {
        // SQLite (тестова БД) не вміє LOWER() для кирилиці — використовуємо вже
        // нижній регістр, щоб перевірити саму логіку фільтра незалежно від цієї особливості.
        $kyivArtist = User::factory()->create(['city' => ['uk' => 'київ', 'en' => 'kyiv']]);
        $lvivArtist = User::factory()->create(['city' => ['uk' => 'львів', 'en' => 'lviv']]);
        Service::factory()->create(['serviceable_type' => User::class, 'serviceable_id' => $kyivArtist->id]);
        Service::factory()->create(['serviceable_type' => User::class, 'serviceable_id' => $lvivArtist->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services?location=київ');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_returns_city_suggestions_by_prefix(): void
    {
        $kryvyiRihArtist = User::factory()->create(['city' => ['uk' => 'Кривий Ріг', 'en' => 'Kryvyi Rih']]);
        $kyivArtist = User::factory()->create(['city' => ['uk' => 'Київ', 'en' => 'Kyiv']]);
        Service::factory()->create(['serviceable_type' => User::class, 'serviceable_id' => $kryvyiRihArtist->id]);
        Service::factory()->create(['serviceable_type' => User::class, 'serviceable_id' => $kyivArtist->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services/locations?search=Крив');

        $response->assertOk()->assertJson(['data' => ['Кривий Ріг']]);
    }

    public function test_location_suggestions_ignore_authors_without_services(): void
    {
        User::factory()->create(['city' => ['uk' => 'Крижопіль', 'en' => 'Kryzhopil']]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services/locations?search=Криж');

        $response->assertOk()->assertJson(['data' => []]);
    }

    public function test_can_search_services_by_title(): void
    {
        Service::factory()->create(['title' => ['uk' => 'Розпис портрету', 'en' => 'Portrait painting']]);
        Service::factory()->create(['title' => ['uk' => 'Пошиття одягу', 'en' => 'Sewing clothes']]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services?search=портрет');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_response_includes_category_filter_tree_with_counts(): void
    {
        $root = ArtCategory::factory()->create(['parent_id' => null]);
        $child = ArtCategory::factory()->create(['parent_id' => $root->id]);
        Service::factory()->create(['art_category_id' => $child->id]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/services');

        $response->assertOk()
            ->assertJsonStructure([
                'filters' => ['categories' => ['*' => ['slug', 'name', 'services_count', 'subcategories']]],
            ]);

        $categories = collect($response->json('filters.categories'));
        $rootFilter = $categories->firstWhere('slug', $root->slug);
        $this->assertNotNull($rootFilter);
        $this->assertSame(1, $rootFilter['services_count']);
    }
}
