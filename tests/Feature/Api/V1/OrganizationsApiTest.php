<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProfileType;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

class OrganizationsApiTest extends ApiTestCase
{
    public function test_can_get_organizations_list(): void
    {
        $organization = User::factory()->create(['profile_type' => ProfileType::Organization]);
        Project::factory()->create([
            'user_id' => $organization->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/organizations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
            ]);
    }

    public function test_organizations_list_does_not_include_artists(): void
    {
        $artist = User::factory()->create(['profile_type' => ProfileType::Artist]);
        Project::factory()->create([
            'user_id' => $artist->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/organizations');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_can_get_organization_profile(): void
    {
        $organization = User::factory()->create(['profile_type' => ProfileType::Organization]);
        Project::factory()->create([
            'user_id' => $organization->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/organizations/{$organization->slug}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'avatar_url']]);
    }

    public function test_returns_404_for_nonexistent_organization(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/organizations/nonexistent-slug');

        $response->assertNotFound();
    }

    public function test_returns_404_for_organization_slug_of_artist(): void
    {
        $artist = User::factory()->create(['profile_type' => ProfileType::Artist]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/organizations/{$artist->slug}");

        $response->assertNotFound();
    }

    public function test_can_get_organization_projects(): void
    {
        $organization = User::factory()->create(['profile_type' => ProfileType::Organization]);
        Project::factory()->count(2)->create([
            'user_id' => $organization->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/organizations/{$organization->slug}/projects");

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
