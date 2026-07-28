<?php

namespace Tests\Feature\Api\V1;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class MyTeamsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    public function test_can_create_team_and_becomes_owner(): void
    {
        $data = ['name' => ['uk' => 'Моя команда']];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/teams', $data);

        $response->assertCreated()->assertJsonPath('data.is_owner', true);

        $team = Team::firstWhere('id', $response->json('data.id'));
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    public function test_can_list_my_teams(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        // Команда без участі юзера
        Team::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/teams');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_non_owner_cannot_update_team(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 0]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/teams/{$team->slug}", ['name' => ['uk' => 'Хак']]);

        $response->assertForbidden();
    }

    public function test_owner_can_update_team(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/teams/{$team->slug}", ['name' => ['uk' => 'Нова назва']]);

        $response->assertOk();
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name->uk' => 'Нова назва']);
    }

    public function test_member_can_leave_team(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create();
        $team->teamMembers()->create(['user_id' => $owner->id, 'role' => 'owner', 'sort_order' => 0]);
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 1]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/teams/{$team->slug}/leave");

        $response->assertOk();
        $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'user_id' => $this->user->id]);
    }

    public function test_owner_cannot_leave_team(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/teams/{$team->slug}/leave");

        $response->assertStatus(422);
    }
}
