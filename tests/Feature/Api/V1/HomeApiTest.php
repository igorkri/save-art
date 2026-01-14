<?php

namespace Tests\Feature\Api\V1;

use App\Models\HomePage;

class HomeApiTest extends ApiTestCase
{
    // ==========================================
    // Головна сторінка
    // ==========================================

    public function test_can_get_home_page_data(): void
    {
        HomePage::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/home');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'hero',
                    'donates_section',
                    'statistics',
                    'partners',
                ],
            ]);
    }

    public function test_returns_404_when_no_active_home_page(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/home');

        $response->assertNotFound();
    }

    // ==========================================
    // Статистика
    // ==========================================

    public function test_can_get_home_statistics(): void
    {
        HomePage::factory()->create([
            'is_active' => true,
            'total_collected' => 100000,
            'active_projects' => 10,
            'completed_projects' => 5,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/home/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_collected',
                    'active_projects',
                    'completed_projects',
                ],
            ]);
    }

    // ==========================================
    // Графік
    // ==========================================

    public function test_can_get_home_chart(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/home/chart');

        $response->assertOk();
    }

    // ==========================================
    // API Key захист
    // ==========================================

    public function test_home_requires_api_key(): void
    {
        // Налаштовуємо API key щоб middleware перевіряв його
        config(['services.api_key' => $this->apiKey]);
        
        // Запит без API key повинен повернути 403
        $response = $this->getJson('/api/home');

        $response->assertForbidden();
    }
}
