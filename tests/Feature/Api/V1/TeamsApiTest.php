<?php

namespace Tests\Feature\Api\V1;

use App\Models\Service;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

class TeamsApiTest extends ApiTestCase
{
    public function test_can_get_teams_list(): void
    {
        Team::factory()->count(2)->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/teams');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['*' => ['id', 'slug', 'name']]])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_team_profile_with_members(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'sort_order' => 0]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/teams/{$team->slug}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.members')
            ->assertJsonPath('data.members.0.id', $member->id);
    }

    public function test_returns_404_for_nonexistent_team(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/teams/nonexistent-slug');

        $response->assertNotFound();
    }

    public function test_can_get_team_services(): void
    {
        $team = Team::factory()->create();
        Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/teams/{$team->slug}/services");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.performer_type', 'team');
    }
}
