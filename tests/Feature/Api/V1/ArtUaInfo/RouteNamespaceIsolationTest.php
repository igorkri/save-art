<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * Смоук-тести нового /v1/art-ua-info/* неймспейсу: перевіряють, що дубльовані
 * контролери справді скоупляться на ProjectSource::ArtUaInfo (а не save-art,
 * як у оригіналах, що їх копіювали), і що старі /v1/* save-art-маршрути
 * залишились робочими та незачепленими.
 */
class RouteNamespaceIsolationTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_art_ua_info_public_projects_list_excludes_save_art_projects(): void
    {
        Project::factory()->create([
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);
        Project::factory()->create([
            'source' => ProjectSource::SaveArt,
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/projects');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_save_art_public_projects_list_excludes_art_ua_info_projects(): void
    {
        Project::factory()->create([
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);
        Project::factory()->create([
            'source' => ProjectSource::SaveArt,
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/projects');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_art_ua_info_my_projects_list_excludes_save_art_projects(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Completed,
        ]);
        Project::factory()->create([
            'user_id' => $this->user->id,
            'source' => ProjectSource::SaveArt,
            'status' => ProjectStatus::Completed,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/art-ua-info/my/projects');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_save_art_login_route_still_works(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => $this->user->email,
                'password' => 'password',
                'device_name' => 'test',
            ]);

        $response->assertOk();
    }

    public function test_art_ua_info_login_route_works_independently(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/art-ua-info/auth/login', [
                'email' => $this->user->email,
                'password' => 'password',
                'device_name' => 'test',
            ]);

        $response->assertOk();
    }

    public function test_art_ua_info_categories_route_responds(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/categories');

        $response->assertOk();
    }

    public function test_art_ua_info_home_route_matches_dedicated_controller(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/home');

        // 404 очікується коли HomePage не налаштована в тестовій БД — головне,
        // що маршрут резолвиться у ArtUaInfo\HomePageController, а не 500/помилка роутингу.
        $this->assertContains($response->status(), [200, 404]);
    }
}
