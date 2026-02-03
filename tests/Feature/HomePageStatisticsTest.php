<?php

namespace Tests\Feature;

use App\Models\HomePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_returns_full_data_when_active(): void
    {
        config(['services.api_key' => 'test-api-key']);

        // Создаём домашнюю страницу с активной статистикой и предустановленными данными
        HomePage::factory()->create([
            'is_active' => true,
            'statistics_is_active' => true,
            'total_collected' => 150000,
            'declared_projects' => 10,
            'active_projects' => 5,
            'completed_projects' => 3,
            'sold_projects' => 2,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => 'test-api-key'])
            ->get('/api/home/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'is_active',
                    'total_collected',
                    'declared_projects',
                    'active_projects',
                    'completed_projects',
                    'sold_projects',
                ]
            ])
            ->assertJson([
                'data' => [
                    'is_active' => true,
                    'total_collected' => 150000.0,
                    'declared_projects' => 10,
                    'active_projects' => 5,
                    'completed_projects' => 3,
                    'sold_projects' => 2,
                ]
            ]);
    }

    public function test_statistics_returns_only_is_active_false_when_inactive(): void
    {
        config(['services.api_key' => 'test-api-key']);

        // Создаём домашнюю страницу с неактивной статистикой
        HomePage::factory()->create([
            'is_active' => true,
            'statistics_is_active' => false,
            'total_collected' => 999999, // Эти значения НЕ должны использоваться
            'declared_projects' => 999,
            'active_projects' => 999,
            'completed_projects' => 999,
            'sold_projects' => 999,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => 'test-api-key'])
            ->get('/api/home/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'is_active',
                    'total_collected',
                    'declared_projects',
                    'active_projects',
                    'completed_projects',
                    'sold_projects',
                ]
            ])
            ->assertJson([
                'data' => ['is_active' => false]
            ]);

        // Проверяем что данные НЕ из HomePage (должны быть вычислены из проектов)
        $data = $response->json('data');
        $this->assertNotEquals(999999, $data['total_collected'], 'Данные не должны браться из HomePage когда statistics_is_active=false');
        $this->assertNotEquals(999, $data['declared_projects'], 'Данные не должны браться из HomePage когда statistics_is_active=false');
    }

    public function test_statistics_defaults_to_active_when_no_home_page(): void
    {
        config(['services.api_key' => 'test-api-key']);

        // Нет записи домашней страницы
        $response = $this->withHeaders(['X-Api-Key' => 'test-api-key'])
            ->get('/api/home/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'is_active',
                    'total_collected',
                    'declared_projects',
                    'active_projects',
                    'completed_projects',
                    'sold_projects',
                ]
            ])
            ->assertJson([
                'data' => ['is_active' => true]
            ]);
    }
}
