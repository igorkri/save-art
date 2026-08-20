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

    public function test_team_projects_excludes_members_personal_projects(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();
        $team->teamMembers()->create(['user_id' => $member->id, 'role' => 'member', 'sort_order' => 0]);

        // Особистий проєкт учасника (без team_id) не має показуватись у проєктах команди.
        Project::factory()->create([
            'user_id' => $member->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/teams/{$team->slug}/projects");

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_team_projects_excludes_drafts(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create();

        Project::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
            'user_type' => 'team',
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/teams/{$team->slug}/projects");

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_team_show_includes_profile_fields_and_members(): void
    {
        $team = Team::factory()->create([
            'description' => 'Опис команди',
            'specialization' => 'Музичний гурт',
        ]);
        $member = User::factory()->create();
        $team->teamMembers()->create(['user_id' => $member->id, 'role' => 'member', 'sort_order' => 0]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/teams/{$team->slug}");

        $response->assertOk()
            ->assertJsonPath('data.description', 'Опис команди')
            ->assertJsonPath('data.specialization', 'Музичний гурт')
            ->assertJsonPath('data.members.0.slug', $member->slug);
    }

    public function test_team_projects_includes_projects_owned_directly_by_the_team(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create();
        // Автор не є учасником команди — проєкт все одно має потрапити в список, бо team_id збігається.

        Project::factory()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
            'user_type' => 'team',
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/teams/{$team->slug}/projects");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.author.type', 'team')
            ->assertJsonPath('data.0.author.slug', $team->slug);
    }

    public function test_returns_404_for_nonexistent_team(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/teams/nonexistent-slug');

        $response->assertNotFound();
    }
}
