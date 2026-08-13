<?php

namespace Tests\Feature\Api;

use App\Models\HomePage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Вимикаємо перевірку API ключа для тестів
        config(['services.api_key' => '']);

        // Створюємо активну головну сторінку
        HomePage::factory()->create([
            'is_active' => true,
            'hero_title' => 'Заголовок українською',
            'donates_title' => 'Донати українською',
            'partners_title' => 'Партнери українською',
        ]);
    }

    public function test_home_page_returns_ukrainian_content(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
            ->assertJsonPath('data.hero.title', 'Заголовок українською')
            ->assertJsonPath('data.donates_section.title', 'Донати українською')
            ->assertJsonPath('data.partners.title', 'Партнери українською');
    }

    public function test_home_page_ignores_language_query_parameter(): void
    {
        $response = $this->getJson('/api/home?language=en');

        $response->assertStatus(200)
            ->assertJsonPath('data.hero.title', 'Заголовок українською');
    }

    public function test_projects_have_ukrainian_fields(): void
    {
        $user = User::factory()->create();

        Project::factory()->create([
            'user_id' => $user->id,
            'title' => [
                'uk' => 'Проект українською',
                'en' => 'Project in English',
            ],
            'short_description' => [
                'uk' => 'Опис українською',
                'en' => 'Description in English',
            ],
            'status' => 'announced',
        ]);

        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
            ->assertJsonPath('data.featured_projects.0.title', 'Проект українською')
            ->assertJsonPath('data.featured_projects.0.short_description', 'Опис українською');
    }
}
