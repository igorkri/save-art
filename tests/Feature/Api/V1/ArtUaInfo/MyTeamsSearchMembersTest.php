<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

class MyTeamsSearchMembersTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_find_user_by_full_name(): void
    {
        User::factory()->create(['full_name' => ['uk' => 'Іван Франко', 'en' => 'Ivan Franko']]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/art-ua-info/my/teams/search-members?search=Франко');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_find_user_by_email(): void
    {
        User::factory()->create(['email' => 'unique-search-target@example.com']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/art-ua-info/my/teams/search-members?search=unique-search-target');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_excludes_blocked_users(): void
    {
        User::factory()->create([
            'full_name' => ['uk' => 'Заблокований Юзер', 'en' => 'Blocked User'],
            'is_blocked' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/art-ua-info/my/teams/search-members?search=Заблокований');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_excludes_current_user(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/art-ua-info/my/teams/search-members?search='.$this->user->email);

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_empty_search_returns_empty_list(): void
    {
        User::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/art-ua-info/my/teams/search-members');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_requires_authentication(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/my/teams/search-members?search=test');

        $response->assertUnauthorized();
    }
}
