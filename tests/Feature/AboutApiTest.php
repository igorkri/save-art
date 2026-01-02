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

        // Создаем тестовые данные About
        About::create([
            'title' => [
                'en' => 'Test Project Tasks',
                'uk' => 'Тестові задачі проєкту',
            ],
            'feats' => [
                'en' => [
                    [
                        'name' => 'Test Feature',
                        'title' => 'Test Feature Title',
                        'description' => 'Test feature description',
                    ],
                ],
                'uk' => [
                    [
                        'name' => 'Тестова функція',
                        'title' => 'Заголовок тестової функції',
                        'description' => 'Опис тестової функції',
                    ],
                ],
                'feat_image' => 'about/test-image.webp',
            ],
            'description' => [
                'en' => 'Test description',
                'uk' => 'Тестовий опис',
                'icon' => 'about/test-icon.webp',
                'image' => 'about/test-bg.webp',
                'title_date' => ['en' => '01.01', 'uk' => '01.01'],
                'description_date' => [
                    'en' => 'Test date description',
                    'uk' => 'Тестовий опис дати',
                ],
                'text' => [
                    'en' => '<p>Test text content</p>',
                    'uk' => '<p>Тестовий текстовий контент</p>',
                ],
            ],
            'goals' => [
                'task' => [
                    'en' => 'Test goal task',
                    'uk' => 'Тестове завдання цілі',
                ],
                'image' => 'about/test-goals.webp',
                'title' => [
                    'en' => 'Test Goal Title',
                    'uk' => 'Заголовок тестової цілі',
                ],
                'description' => [
                    'en' => '<p>Test goal description</p>',
                    'uk' => '<p>Опис тестової цілі</p>',
                ],
            ],
            'tasks' => [
                'en' => [
                    ['task' => 'Test task 1'],
                    ['task' => 'Test task 2'],
                ],
                'uk' => [
                    ['task' => 'Тестове завдання 1'],
                    ['task' => 'Тестове завдання 2'],
                ],
            ],
            'implementation' => [
                'image' => 'about/test-implementation.webp',
                'title' => ['en' => 'Test Implementation', 'uk' => 'Тестова реалізація'],
                'items' => [
                    'en' => [
                        ['item' => ['title' => 'Test item', 'description' => 'Test description']],
                    ],
                    'uk' => [
                        ['item' => ['title' => 'Тестовий пункт', 'description' => 'Тестовий опис']],
                    ],
                ],
            ],
            'results' => [
                'title' => ['en' => 'Test Result', 'uk' => 'Тестовий результат'],
                'description' => [
                    'en' => '<h6>Test result description</h6>',
                    'uk' => '<h6>Опис тестового результату</h6>',
                ],
            ],
            'id_art' => [
                'image' => 'about/test-id-art.webp',
                'title' => ['en' => 'Test ID Art', 'uk' => 'Тестовий ID Art'],
                'description' => [
                    'en' => '<p>Test ID Art description</p>',
                    'uk' => '<p>Опис тестового ID Art</p>',
                ],
            ],
            'events' => [
                'h2' => [
                    'en' => '<h2>Test events</h2>',
                    'uk' => '<h2>Тестові події</h2>',
                ],
                'title' => [
                    'en' => 'Test Events',
                    'uk' => 'Тестові події',
                ],
            ],
            'project' => [
                'image' => 'about/project/test-project.webp',
                'image_bg' => 'about/project/test-project-bg.webp',
                'description' => [
                    'en' => '<p>Test project description</p>',
                    'uk' => '<p>Опис тестового проєкту</p>',
                ],
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
        $this->assertArrayHasKey('uk', $data['data']['title']);
        $this->assertArrayHasKey('en', $data['data']['title']);
        $this->assertEquals('Тестові задачі проєкту', $data['data']['title']['uk']);
        $this->assertEquals('Test Project Tasks', $data['data']['title']['en']);
    }

    public function test_can_get_about_data_by_language_ukrainian(): void
    {
        $response = $this->get('/api/about/language/uk');

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
        // При фільтрації по мові title стає рядком, а не масивом
        $this->assertEquals('Тестові задачі проєкту', $data['data']['title']);
        // Перевіряємо feats структуру після фільтрації
        $this->assertIsArray($data['data']['feats']);
    }

    public function test_can_get_about_data_by_language_english(): void
    {
        $response = $this->get('/api/about/language/en');

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
        // При фільтрації по мові title стає рядком, а не масивом
        $this->assertEquals('Test Project Tasks', $data['data']['title']);
        // Перевіряємо feats структуру після фільтрації
        $this->assertIsArray($data['data']['feats']);
    }

    public function test_image_urls_are_formatted_correctly(): void
    {
        $response = $this->get('/api/about');

        $data = $response->json('data');

        // Проверяем что изображения содержат правильные пути
        $this->assertStringContainsString('test-image.webp', $data['feats']['feat_image']);
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
        $response = $this->get('/api/about/language/invalid-lang');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('uk', $data['language']);
    }
}
