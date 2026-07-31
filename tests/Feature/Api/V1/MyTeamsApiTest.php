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

    /**
     * @return array<string, mixed>
     */
    private function validTeamPayload(array $overrides = []): array
    {
        // 1x1 прозорий PNG — валідні бінарні дані зображення для процесора
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        return array_merge([
            'name' => ['uk' => 'Моя команда', 'en' => 'My Team'],
            'avatar' => $base64Image,
            'country' => ['uk' => 'Україна', 'en' => 'Ukraine'],
            'city' => ['uk' => 'Київ', 'en' => 'Kyiv'],
            'region' => ['uk' => 'Київська область', 'en' => 'Kyiv region'],
            'zip' => ['uk' => '01001', 'en' => '01001'],
            'description' => ['uk' => 'Опис команди', 'en' => 'Team description'],
            'specialization' => ['uk' => 'Відеозйомка', 'en' => 'Video shooting'],
        ], $overrides);
    }

    public function test_can_create_team_and_becomes_owner(): void
    {
        $data = $this->validTeamPayload();

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
            ->putJson("/api/v1/my/teams/{$team->slug}", $this->validTeamPayload(['name' => ['uk' => 'Хак', 'en' => 'Hack']]));

        $response->assertForbidden();
    }

    public function test_owner_can_update_team(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/teams/{$team->slug}", $this->validTeamPayload(['name' => ['uk' => 'Нова назва', 'en' => 'New name']]));

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

    public function test_deleting_team_removes_avatar_file_from_storage(): void
    {
        $create = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/teams', $this->validTeamPayload());
        $avatarPath = Team::firstWhere('id', $create->json('data.id'))->avatar;

        Storage::disk('public')->assertExists($avatarPath);

        $slug = $create->json('data.slug');
        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/teams/{$slug}")
            ->assertOk();

        Storage::disk('public')->assertMissing($avatarPath);
    }

    public function test_replacing_avatar_on_update_deletes_old_file(): void
    {
        $create = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/teams', $this->validTeamPayload());
        $oldAvatarPath = Team::firstWhere('id', $create->json('data.id'))->avatar;
        $slug = $create->json('data.slug');

        // Інше 1x1 PNG зображення — щоб отримати інший UUID-файл при заміні
        $newAvatar = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/teams/{$slug}", $this->validTeamPayload(['avatar' => $newAvatar]));

        $response->assertOk();
        $newAvatarPath = Team::firstWhere('id', $create->json('data.id'))->avatar;

        $this->assertNotSame($oldAvatarPath, $newAvatarPath);
        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($newAvatarPath);
    }

    public function test_keeping_same_avatar_path_on_update_does_not_delete_file(): void
    {
        $create = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/teams', $this->validTeamPayload());
        $avatarPath = Team::firstWhere('id', $create->json('data.id'))->avatar;
        $slug = $create->json('data.slug');

        // Так фронтенд відправляє незмінений аватар при редагуванні інших полів —
        // відносний шлях до вже завантаженого файлу, а не Base64.
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/teams/{$slug}", $this->validTeamPayload(['avatar' => $avatarPath]));

        $response->assertOk();
        $this->assertSame($avatarPath, Team::firstWhere('id', $create->json('data.id'))->avatar);
        Storage::disk('public')->assertExists($avatarPath);
    }
}
