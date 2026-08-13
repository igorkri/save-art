<?php

namespace Tests\Feature\Api\V1;

use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MyServicesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    public function test_can_get_my_services(): void
    {
        Service::factory()->count(2)->create([
            'serviceable_type' => User::class,
            'serviceable_id' => $this->user->id,
        ]);
        // Чужа послуга
        Service::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/services');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_create_service(): void
    {
        $category = ArtCategory::factory()->create();

        $data = [
            'title' => 'Розпис портрету',
            'description' => 'Опис послуги',
            'image' => UploadedFile::fake()->image('cover.jpg'),
            'art_category' => $category->slug,
            'price' => 1500,
            'currency' => 'UAH',
            'options' => [
                ['name' => ['uk' => 'Ескіз']],
            ],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/services?language=uk', $data);

        $response->assertCreated()
            ->assertJsonPath('data.price', 1500)
            ->assertJsonPath('data.options.0', 'Ескіз');

        $this->assertDatabaseHas('services', [
            'serviceable_type' => User::class,
            'serviceable_id' => $this->user->id,
            'art_category_id' => $category->id,
        ]);
    }

    public function test_cannot_create_service_without_title(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/services', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['title', 'art_category']);
    }

    public function test_cannot_update_others_service(): void
    {
        $category = ArtCategory::factory()->create();
        $service = Service::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/services/{$service->slug}", [
                'title' => 'Хак',
                'art_category' => $category->slug,
            ]);

        $response->assertForbidden();
    }

    public function test_can_delete_own_service(): void
    {
        Storage::disk('public')->put('services/old-cover.jpg', 'fake-content');

        $service = Service::factory()->create([
            'serviceable_type' => User::class,
            'serviceable_id' => $this->user->id,
            'image' => 'services/old-cover.jpg',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/services/{$service->slug}");

        $response->assertOk();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        Storage::disk('public')->assertMissing('services/old-cover.jpg');
    }

    public function test_updating_image_deletes_old_file(): void
    {
        $category = ArtCategory::factory()->create();
        Storage::disk('public')->put('services/old-cover.jpg', 'fake-content');

        $service = Service::factory()->create([
            'serviceable_type' => User::class,
            'serviceable_id' => $this->user->id,
            'image' => 'services/old-cover.jpg',
        ]);

        // 1x1 прозорий PNG — валідні бінарні дані зображення для процесора
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/services/{$service->slug}", [
                'title' => $service->title ?? 'Послуга',
                'art_category' => $category->slug,
                'image' => $base64Image,
            ]);

        $response->assertOk();

        Storage::disk('public')->assertMissing('services/old-cover.jpg');

        $service->refresh();
        $this->assertNotSame('services/old-cover.jpg', $service->image);
        Storage::disk('public')->assertExists($service->image);
    }
}
