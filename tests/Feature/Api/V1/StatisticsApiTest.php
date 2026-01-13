<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class StatisticsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_get_platform_statistics(): void
    {
        // Створюємо тестові дані
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Announced->value,
            'budget_goal' => 100000,
            'budget_collected' => 50000,
        ]);

        Donation::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'completed',
        ]);

        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'data' => [
                    'platform' => [
                        'total_collected',
                        'total_projects',
                        'active_projects',
                        'completed_projects',
                        'sold_projects',
                        'total_supporters',
                        'total_artists',
                    ],
                    'monthly',
                    'by_art_form',
                ],
            ])
            ->assertJson([
                'result' => true,
            ]);
    }

    public function test_can_get_project_statistics(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Announced->value,
        ]);
        Project::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Completed->value,
        ]);

        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'data' => [
                    'period',
                    'by_status',
                    'by_category',
                    'top_projects',
                ],
            ])
            ->assertJson([
                'result' => true,
                'data' => [
                    'period' => 'all',
                ],
            ]);
    }

    public function test_can_filter_project_statistics_by_period(): void
    {
        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics/projects?period=year');

        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
                'data' => [
                    'period' => 'year',
                ],
            ]);

        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics/projects?period=month');

        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
                'data' => [
                    'period' => 'month',
                ],
            ]);
    }

    public function test_can_get_donation_statistics(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Announced->value,
        ]);

        Donation::factory()->count(5)->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'amount' => 500,
            'status' => 'completed',
        ]);

        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics/donations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'data' => [
                    'period',
                    'summary' => [
                        'total',
                        'count',
                        'average',
                    ],
                    'timeline',
                    'distribution',
                ],
            ])
            ->assertJson([
                'result' => true,
                'data' => [
                    'period' => 'year',
                ],
            ]);
    }

    public function test_can_filter_donation_statistics_by_period(): void
    {
        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics/donations?period=week');

        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
                'data' => [
                    'period' => 'week',
                ],
            ]);

        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics/donations?period=month');

        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
                'data' => [
                    'period' => 'month',
                ],
            ]);
    }

    public function test_statistics_are_cached(): void
    {
        // Перший запит - дані кешуються
        $this->withHeaders($this->apiHeaders())->getJson('/api/v1/statistics')->assertStatus(200);

        $this->assertTrue(Cache::has('platform_statistics'));
    }
}
