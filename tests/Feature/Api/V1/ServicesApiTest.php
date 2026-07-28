<?php

namespace Tests\Feature\Api\V1;

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
}
