<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

class TeamsApiTest extends ApiTestCase
{
    public function test_can_get_teams_list(): void
    {
        Team::factory()->create();

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/teams');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'slug', 'members_count', 'member_avatars']],
            ]);
    }

    public function test_team_projects_aggregates_members_published_projects(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();
        $team->teamMembers()->create(['user_id' => $member->id, 'role' => 'member', 'sort_order' => 0]);

        Project::factory()->create([
            'user_id' => $member->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);
        // Чернетка не показується.
        Project::factory()->create([
            'user_id' => $member->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/teams/{$team->slug}/projects");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_returns_404_for_nonexistent_team(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/teams/nonexistent-slug');

        $response->assertNotFound();
    }
}
