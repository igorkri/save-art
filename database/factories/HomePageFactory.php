<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HomePage>
 *
 * Factory for HomePage model
 *
 * приклад використання:
 * HomePage::factory()->create();
 * HomePage::factory()->count(5)->create();
 * HomePage::factory()->state(['is_active' => false])->create();
  HomePage::factory()->count(3)->state(new Sequence(
      ['is_active' => true],
      ['is_active' => false],
  ))->create();
 */
class HomePageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hero_title' => 'Мистецтво допомоги — найсучасніше з мистецтв',
            'hero_video_poster' => 'hero-videos/hero.webp',
            'hero_video_poster_m' => 'hero-videos/hero-mobile.webp',
            'hero_image_poster' => 'hero-images/hero-image.webp',
            'hero_image_poster_m' => 'hero-images/hero-image-mobile.webp',

            'donates_subtitle' => 'ДОЛУЧАЙТЕСЬ ДО ВІДРОДЖЕННЯ ТА РОЗВИТКУ УКРАЇНСЬКОЇ КУЛЬТУРИ',
            'donates_title' => 'Твоя підтримка — натхнення для митця',
            'donates_text' => 'Ми пропонуємо прозору систему донатів, яка забезпечить майбутній проєкт в будь-якій галузі мистецтва стабільною підтримкою. Донатерами можуть бути як фізичні так і юридичні особи. Навіть 10₴ допоможуть митцю реалізувати свій творчий потенціал.',

            'total_collected' => 2325250,
            'declared_projects' => 624,
            'active_projects' => 387,
            'completed_projects' => 1126,
            'sold_projects' => 107,

            'partners_title' => 'Партнери',
            'partners' => [
                [
                    'logo' => 'partners/partner1.webp',
                    'name' => 'Партнер 1',
                    'description' => 'Опис партнера 1',
                ],
                [
                    'logo' => 'partners/partner2.webp',
                    'name' => 'Партнер 2',
                    'description' => 'Опис партнера 2',
                ],
            ],

            'ad_first_title' => 'Долучайтесь до Мистецтва Перемоги!',
            'ad_first_button_text' => 'Підтримати платформу',
            'ad_first_image' => 'advertising/advert1.webp',

            'ad_second_title' => 'Ваша допомога та підтримка стане світловим імпульсом відбудови сучасного ренесансу!',
            'ad_second_button_text' => 'Підтримати митців',
            'ad_second_image' => 'advertising/advert2.webp',

            'footer_expert_title' => 'Запрошуємо експертів до співпраці',
            'footer_expert_text' => 'Благодійний фонд ID_Art UA відкритий до співпраці з експертами у галузі мистецтва, кураторами, галереями та колекціонерами.',
            'footer_expert_features' => [
                'Створення сучасного українського мистецтва',
                'Участь у проведенні виставок та мистецьких заходів',
                'Популяризація українських митців в усьому світі',
            ],
            'footer_expert_button_text' => 'Відправити заявку',

            'platform_description_tagline' => 'art-ua',
            'platform_description_title' => 'Про платформу',
            'platform_description_subtitle' => "Спільнота підтримки\nукраїнських митців",
            'platform_description_paragraphs' => [
                'Ми створюємо всеукраїнську платформу особистостей з різних культурних сфер діяльності.',
                'Для подолання глобальної культурної кризи в країні ми надаємо можливість формувати новітню мистецьку спадщину.',
            ],
            'platform_features' => [
                ['title' => 'Творче натхнення', 'description' => 'Досліджуй роботи інших, надихайся і вдосконалюй свої власні проєкти.'],
                ['title' => 'Визнання та нові можливості', 'description' => 'Підвищуй свою видимість і знайди нові проєкти та співпрацю.'],
                ['title' => 'Навчання у кращих', 'description' => 'Відвідуй вебінари та майстер-класи.'],
            ],

            'is_active' => true,
            'statistics_is_active' => true,
        ];
    }
}
