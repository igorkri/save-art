<?php

namespace Tests\Feature\Api\V1;

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
        $data = [
            'title' => ['uk' => 'Розпис портрету'],
            'description' => ['uk' => 'Опис послуги'],
            'image' => UploadedFile::fake()->image('cover.jpg'),
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
        ]);
    }

    public function test_cannot_create_service_without_title(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/services', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['title.uk']);
    }

    public function test_cannot_update_others_service(): void
    {
        $service = Service::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/services/{$service->slug}", ['title' => ['uk' => 'Хак']]);

        $response->assertForbidden();
    }

    public function test_can_delete_own_service(): void
    {
        $service = Service::factory()->create([
            'serviceable_type' => User::class,
            'serviceable_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/services/{$service->slug}");

        $response->assertOk();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }
}
