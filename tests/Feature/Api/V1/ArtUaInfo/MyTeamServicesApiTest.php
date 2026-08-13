<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\V1\ApiTestCase;

class MyTeamServicesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    public function test_member_can_list_team_services(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 0]);

        Service::factory()->count(2)->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
        ]);
        // Послуга іншої команди не має потрапляти в список.
        Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => Team::factory()->create()->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/art-ua-info/my/teams/{$team->slug}/services");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_non_member_cannot_list_team_services(): void
    {
        $team = Team::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/art-ua-info/my/teams/{$team->slug}/services");

        $response->assertForbidden();
    }

    public function test_member_can_create_team_service(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 0]);
        $category = ArtCategory::factory()->create();

        $data = [
            'title' => 'Послуга команди',
            'description' => 'Опис послуги команди',
            'art_category' => $category->slug,
            'price' => 2000,
            'currency' => 'UAH',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/art-ua-info/my/teams/{$team->slug}/services?language=uk", $data);

        $response->assertCreated()
            ->assertJsonPath('data.performer_type', 'team')
            ->assertJsonPath('data.performer.slug', $team->slug);

        $this->assertDatabaseHas('services', [
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
            'art_category_id' => $category->id,
        ]);
    }

    public function test_non_member_cannot_create_team_service(): void
    {
        $team = Team::factory()->create();
        $category = ArtCategory::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/art-ua-info/my/teams/{$team->slug}/services", [
                'title' => 'Послуга команди',
                'art_category' => $category->slug,
            ]);

        $response->assertForbidden();
    }

    public function test_member_can_update_team_service(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 0]);
        $category = ArtCategory::factory()->create();
        $service = Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/art-ua-info/my/services/{$service->slug}?language=uk", [
                'title' => 'Оновлена назва',
                'art_category' => $category->slug,
            ]);

        $response->assertOk()->assertJsonPath('data.title', 'Оновлена назва');
    }

    public function test_non_member_cannot_update_team_service(): void
    {
        $team = Team::factory()->create();
        $category = ArtCategory::factory()->create();
        $service = Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/art-ua-info/my/services/{$service->slug}", [
                'title' => 'Хак',
                'art_category' => $category->slug,
            ]);

        $response->assertForbidden();
    }

    public function test_member_can_delete_team_service(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 0]);
        $service = Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/art-ua-info/my/services/{$service->slug}");

        $response->assertOk();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }
}
