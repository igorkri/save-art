<?php

namespace Tests\Feature\Api\V1;

use App\Models\ArtCatalog;
use App\Models\ArtCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MyArtCatalogsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    public function test_can_get_my_catalogs(): void
    {
        ArtCatalog::factory()->count(2)->create(['user_id' => $this->user->id]);
        // Чужий каталог
        ArtCatalog::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/catalogs');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_create_catalog(): void
    {
        $category = ArtCategory::factory()->create();

        $data = [
            'title' => ['uk' => 'Мій каталог', 'en' => 'My catalog'],
            'image' => UploadedFile::fake()->image('cover.jpg'),
            'pdf_file' => UploadedFile::fake()->create('catalog.pdf', 100, 'application/pdf'),
            'art_category' => $category->slug,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/catalogs', $data);

        $response->assertCreated();

        $this->assertDatabaseHas('art_catalogs', [
            'user_id' => $this->user->id,
            'art_category_id' => $category->id,
        ]);
    }

    public function test_cannot_create_catalog_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/catalogs', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title.uk', 'title.en', 'image', 'pdf_file', 'art_category']);
    }

    public function test_can_update_own_catalog(): void
    {
        $category = ArtCategory::factory()->create();
        $catalog = ArtCatalog::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/catalogs/{$catalog->id}", [
                '_method' => 'PUT',
                'title' => ['uk' => 'Оновлений каталог', 'en' => 'Updated catalog'],
                'art_category' => $category->slug,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('art_catalogs', [
            'id' => $catalog->id,
            'art_category_id' => $category->id,
        ]);
    }

    public function test_cannot_update_catalog_without_required_fields(): void
    {
        $catalog = ArtCatalog::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/catalogs/{$catalog->id}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title.uk', 'title.en', 'art_category']);
    }

    public function test_cannot_update_others_catalog(): void
    {
        $catalog = ArtCatalog::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/catalogs/{$catalog->id}", ['title' => ['uk' => 'Хак']]);

        $response->assertForbidden();
    }

    public function test_can_delete_own_catalog(): void
    {
        $catalog = ArtCatalog::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/catalogs/{$catalog->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('art_catalogs', ['id' => $catalog->id]);
    }

    public function test_setting_primary_unsets_other_catalogs(): void
    {
        $first = ArtCatalog::factory()->create(['user_id' => $this->user->id, 'is_primary' => true]);
        $second = ArtCatalog::factory()->create(['user_id' => $this->user->id, 'is_primary' => false]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/catalogs/{$second->id}/primary");

        $response->assertOk()->assertJsonPath('data.is_primary', true);

        $this->assertDatabaseHas('art_catalogs', ['id' => $first->id, 'is_primary' => false]);
        $this->assertDatabaseHas('art_catalogs', ['id' => $second->id, 'is_primary' => true]);
    }
}
