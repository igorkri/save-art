<?php

namespace Tests\Feature;

use App\Models\About;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('X-Api-Key', '74j1aF+qYgihMEUlQqhBmbCCZIl8+G8AU8BrYp7+sIc=');

        // Создаем тестовые данные About
        About::create([
            'title' => 'Тестові задачі проєкту',
            'feats' => [
                [
                    'name' => 'Тестова функція',
                    'title' => 'Заголовок тестової функції',
                    'description' => 'Опис тестової функції',
                ],
            ],
            'description' => [
                'icon' => 'about/test-icon.webp',
                'image' => 'about/test-bg.webp',
                'title_date' => '01.01',
                'description_date' => 'Тестовий опис дати',
                'text' => '<p>Тестовий текстовий контент</p>',
            ],
            'goals' => [
                'task' => 'Тестове завдання цілі',
                'image' => 'about/test-goals.webp',
                'title' => 'Заголовок тестової цілі',
                'description' => '<p>Опис тестової цілі</p>',
            ],
            'tasks' => [
                ['task' => 'Тестове завдання 1'],
                ['task' => 'Тестове завдання 2'],
            ],
            'implementation' => [
                'image' => 'about/test-implementation.webp',
                'title' => 'Тестова реалізація',
                'items' => [
                    ['item' => ['title' => 'Тестовий пункт', 'description' => 'Тестовий опис']],
                ],
            ],
            'results' => [
                'title' => 'Тестовий результат',
                'description' => '<h6>Опис тестового результату</h6>',
            ],
            'id_art' => [
                'image' => 'about/test-id-art.webp',
                'title' => 'Тестовий ID Art',
                'description' => '<p>Опис тестового ID Art</p>',
            ],
            'events' => [
                'h2' => '<h2>Тестові події</h2>',
                'title' => 'Тестові події',
            ],
            'project' => [
                'image' => 'about/project/test-project.webp',
                'image_bg' => 'about/project/test-project-bg.webp',
                'description' => '<p>Опис тестового проєкту</p>',
            ],
            'artists' => null,
            'is_active_artist' => true,
            'partners' => null,
        ]);
    }

    public function test_can_get_about_data(): void
    {
        $response = $this->get('/api/about');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'message',
                'data' => [
                    'id',
                    'title',
                    'feats',
                    'description',
                    'goals',
                    'tasks',
                    'implementation',
                    'results',
                    'id_art',
                    'events',
                    'project',
                    'artists',
                    'is_active_artist',
                    'partners',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $data = $response->json();
        $this->assertTrue($data['result']);
        $this->assertEquals('Тестові задачі проєкту', $data['data']['title']);
    }

    public function test_can_get_about_data_by_language_ukrainian(): void
    {
        $response = $this->get('/api/about?language=uk');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'message',
                'data',
                'language',
            ]);

        $data = $response->json();
        $this->assertTrue($data['result']);
        $this->assertEquals('uk', $data['language']);
        $this->assertEquals('Тестові задачі проєкту', $data['data']['title']);
        // Перевіряємо feats структуру після фільтрації
        $this->assertIsArray($data['data']['feats']);
    }

    public function test_can_get_about_data_by_language_english(): void
    {
        // Мультимовність вимкнена (залишилась лише uk), тож `language=en`
        // повертає той самий (єдиний наявний) контент.
        $response = $this->get('/api/about?language=en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'message',
                'data',
                'language',
            ]);

        $data = $response->json();
        $this->assertTrue($data['result']);
        $this->assertEquals('en', $data['language']);
        $this->assertEquals('Тестові задачі проєкту', $data['data']['title']);
        // Перевіряємо feats структуру після фільтрації
        $this->assertIsArray($data['data']['feats']);
    }

    public function test_image_urls_are_formatted_correctly(): void
    {
        $response = $this->get('/api/about');

        $data = $response->json('data');

        // Проверяем что изображения содержат правильные пути
        $this->assertStringContainsString('test-icon.webp', $data['description']['icon']);
        $this->assertStringContainsString('test-bg.webp', $data['description']['image']);
    }

    public function test_returns_404_when_no_about_data_exists(): void
    {
        // Очищаем данные
        About::truncate();

        $response = $this->get('/api/about');

        $response->assertStatus(404)
            ->assertJson([
                'result' => false,
                'message' => 'About data not found',
                'data' => null,
            ]);
    }

    public function test_defaults_to_ukrainian_for_invalid_language(): void
    {
        $response = $this->get('/api/about?language=invalid-lang');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('uk', $data['language']);
    }
}
