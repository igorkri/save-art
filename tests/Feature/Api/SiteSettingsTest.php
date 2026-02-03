<?php

namespace Tests\Feature\Api;

use App\Models\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Вимикаємо перевірку API ключа для тестів
        config(['services.api_key' => '']);

        // Створюємо налаштування сайту
        SiteSettings::create([
            'site_logo' => 'logo.svg',
            'header_brand_name' => [
                'uk' => 'save-art.in.ua',
                'en' => 'save-art.in.ua',
            ],
            'header_dropdown_sites' => [
                ['name' => ['uk' => 'save-art.in.ua', 'en' => 'save-art.in.ua'], 'url' => '/', 'is_active' => true],
            ],
            'header_menu' => [
                ['label' => ['uk' => 'Проєкти', 'en' => 'Projects'], 'url' => '/projects'],
                ['label' => ['uk' => 'Звіти', 'en' => 'Reports'], 'url' => '/reports'],
            ],
            'header_socials' => [
                'instagram' => 'https://instagram.com/',
                'facebook' => 'https://facebook.com/',
            ],
            'header_support_button_url' => '/support-platform',
            'header_support_button_text' => [
                'uk' => 'Підтримати',
                'en' => 'Support',
            ],
            'header_login_button_text' => [
                'uk' => 'Увійти',
                'en' => 'Login',
            ],
            'footer_brand_name' => [
                'uk' => 'save-art.in.ua',
                'en' => 'save-art.in.ua',
            ],
            'footer_slogan' => [
                'uk' => 'Мистецтво допомоги',
                'en' => 'The art of helping',
            ],
            'footer_collaboration_title' => [
                'uk' => 'Запрошуємо експертів до співпраці',
                'en' => 'We invite experts to collaborate',
            ],
            'footer_collaboration_text' => [
                'uk' => 'Благодійний фонд ID_Art UA відкритий до співпраці',
                'en' => 'ID_Art UA Charitable Foundation is open to collaboration',
            ],
            'footer_collaboration_items' => [
                ['image' => null, 'text' => ['uk' => 'Створення мистецтва', 'en' => 'Creating art']],
            ],
            'footer_collaboration_button_text' => [
                'uk' => 'Відправити заявку',
                'en' => 'Send application',
            ],
            'footer_sites_menu' => [
                [
                    'site_name' => ['uk' => 'save-art.in.ua', 'en' => 'save-art.in.ua'],
                    'site_url' => '/',
                    'links' => [
                        ['label' => ['uk' => 'Проєкти', 'en' => 'Projects'], 'url' => '/projects'],
                    ],
                ],
            ],
            'footer_company_name' => [
                'uk' => 'БЛАГОДІЙНИЙ ФОНД ID_Art UA',
                'en' => 'ID_Art UA CHARITABLE FOUNDATION',
            ],
            'footer_address' => [
                'uk' => 'м. Івано-Франківськ, Україна',
                'en' => 'Ivano-Frankivsk, Ukraine',
            ],
            'footer_email' => 'test@example.com',
            'footer_phone' => '+380 67 000 0000',
            'footer_social_links' => [
                ['type' => 'instagram', 'url' => 'https://instagram.com/', 'label' => null],
            ],
            'footer_copyright_year' => '2025',
        ]);
    }

    public function test_footer_returns_ukrainian_by_default(): void
    {
        $response = $this->getJson('/api/site/footer');

        $response->assertStatus(200)
            ->assertJsonPath('data.top.collaboration.title', 'Запрошуємо експертів до співпраці')
            ->assertJsonPath('data.top.collaboration.text', 'Благодійний фонд ID_Art UA відкритий до співпраці')
            ->assertJsonPath('data.top.collaboration.button_text', 'Відправити заявку');
    }

    public function test_footer_returns_english_when_requested(): void
    {
        $response = $this->getJson('/api/site/footer?language=en');

        $response->assertStatus(200)
            ->assertJsonPath('data.top.collaboration.title', 'We invite experts to collaborate')
            ->assertJsonPath('data.top.collaboration.text', 'ID_Art UA Charitable Foundation is open to collaboration')
            ->assertJsonPath('data.top.collaboration.button_text', 'Send application');
    }

    public function test_footer_returns_404_when_no_settings(): void
    {
        SiteSettings::query()->delete();

        $response = $this->getJson('/api/site/footer');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Налаштування сайту не знайдені');
    }

    public function test_header_returns_basic_structure(): void
    {
        $response = $this->getJson('/api/site/header');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'logo',
                    'brand_name',
                    'dropdown_sites',
                    'menu',
                    'socials',
                    'support_button',
                    'login_button',
                ],
            ]);
    }

    public function test_header_accepts_language_parameter(): void
    {
        $response = $this->getJson('/api/site/header?language=en');

        $response->assertStatus(200)
            ->assertJsonPath('data.menu.0.label', 'Projects')
            ->assertJsonPath('data.support_button.text', 'Support')
            ->assertJsonPath('data.login_button.text', 'Login');
    }

    public function test_settings_returns_both_header_and_footer(): void
    {
        $response = $this->getJson('/api/site/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'header',
                    'footer',
                ],
            ]);
    }

    public function test_home_page_no_longer_returns_footer_expert(): void
    {
        // Створюємо активну HomePage для тесту
        \App\Models\HomePage::factory()->create(['is_active' => true]);

        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
            ->assertJsonMissing(['footer_expert']);
    }
}
