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

        // Створюємо активну головну сторінку з мультимовним контентом
        HomePage::factory()->create([
            'is_active' => true,
            'hero_title' => [
                'uk' => 'Заголовок українською',
                'en' => 'Title in English',
            ],
            'donates_title' => [
                'uk' => 'Донати українською',
                'en' => 'Donations in English',
            ],
            'partners_title' => [
                'uk' => 'Партнери українською',
                'en' => 'Partners in English',
            ],
        ]);
    }

    public function test_home_page_returns_ukrainian_by_default(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
            ->assertJsonPath('data.hero.title', 'Заголовок українською')
            ->assertJsonPath('data.donates_section.title', 'Донати українською')
            ->assertJsonPath('data.partners.title', 'Партнери українською');
    }

    public function test_home_page_returns_english_when_requested(): void
    {
        $response = $this->getJson('/api/home?language=en');

        $response->assertStatus(200)
            ->assertJsonPath('data.hero.title', 'Title in English')
            ->assertJsonPath('data.donates_section.title', 'Donations in English')
            ->assertJsonPath('data.partners.title', 'Partners in English');
    }

    public function test_home_page_returns_ukrainian_for_invalid_language(): void
    {
        $response = $this->getJson('/api/home?language=fr');

        $response->assertStatus(200)
            ->assertJsonPath('data.hero.title', 'Заголовок українською');
    }

    public function test_projects_have_translated_fields(): void
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

        $response = $this->getJson('/api/home?language=en');

        $response->assertStatus(200)
            ->assertJsonPath('data.featured_projects.0.title', 'Project in English')
            ->assertJsonPath('data.featured_projects.0.short_description', 'Description in English');
    }
}
