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
            'hero_title' => [
                'uk' => 'Мистецтво допомоги — найсучасніше з мистецтв',
                'en' => 'The Art of Help — the Most Contemporary of Arts'
            ],
            'hero_video_poster' => 'hero-videos/hero.webp',
            'hero_video_poster_m' => 'hero-videos/hero-mobile.webp',
            'hero_image_poster' => 'hero-images/hero-image.webp',
            'hero_image_poster_m' => 'hero-images/hero-image-mobile.webp',

            'donates_subtitle' => [
                'uk' => 'ДОЛУЧАЙТЕСЬ ДО ВІДРОДЖЕННЯ ТА РОЗВИТКУ УКРАЇНСЬКОЇ КУЛЬТУРИ',
                'en' => 'JOIN THE REVIVAL AND DEVELOPMENT OF UKRAINIAN CULTURE'
            ],
            'donates_title' => [
                'uk' => 'Твоя підтримка — натхнення для митця',
                'en' => 'Your Support — Inspiration for the Artist'
            ],
            'donates_text' => [
                'uk' => 'Ми пропонуємо прозору систему донатів, яка забезпечить майбутній проєкт в будь-якій галузі мистецтва стабільною підтримкою. Донатерами можуть бути як фізичні так і юридичні особи. Навіть 10₴ допоможуть митцю реалізувати свій творчий потенціал.',
                'en' => 'We offer a transparent donation system that will provide future projects in any field of art with stable support. Donors can be both individuals and legal entities. Even ₴10 will help an artist realize their creative potential.'
            ],

            'total_collected' => 2325250,
            'declared_projects' => 624,
            'active_projects' => 387,
            'completed_projects' => 1126,
            'sold_projects' => 107,

            'partners_title' => [
                'uk' => 'Партнери',
                'en' => 'Partners'
            ],
            'partners' => [
                [
                    'logo' => 'partners/partner1.webp',
                    'name' => [
                        'uk' => 'Партнер 1',
                        'en' => 'Partner 1'
                    ],
                    'description' => [
                        'uk' => 'Опис партнера 1',
                        'en' => 'Description of partner 1'
                    ]
                ],
                [
                    'logo' => 'partners/partner2.webp',
                    'name' => [
                        'uk' => 'Партнер 2',
                        'en' => 'Partner 2'
                    ],
                    'description' => [
                        'uk' => 'Опис партнера 2',
                        'en' => 'Description of partner 2'
                    ]
                ]
            ],

            'ad_first_title' => [
                'uk' => 'Долучайтесь до Мистецтва Перемоги!',
                'en' => 'Join the Art of Victory!'
            ],
            'ad_first_button_text' => [
                'uk' => 'Підтримати платформу',
                'en' => 'Support the Platform'
            ],
            'ad_first_image' => 'advertising/advert1.webp',

            'ad_second_title' => [
                'uk' => 'Ваша допомога та підтримка стане світловим імпульсом відбудови сучасного ренесансу!',
                'en' => 'Your help and support will become a light impulse for the reconstruction of a modern renaissance!'
            ],
            'ad_second_button_text' => [
                'uk' => 'Підтримати митців',
                'en' => 'Support Artists'
            ],
            'ad_second_image' => 'advertising/advert2.webp',

            'footer_expert_title' => [
                'uk' => 'Запрошуємо експертів до співпраці',
                'en' => 'We Invite Experts to Cooperate'
            ],
            'footer_expert_text' => [
                'uk' => 'Благодійний фонд ID_Art UA відкритий до співпраці з експертами у галузі мистецтва, кураторами, галереями та колекціонерами.',
                'en' => 'ID_Art UA Charitable Foundation is open for cooperation with art experts, curators, galleries and collectors.'
            ],
            'footer_expert_features' => [
                'uk' => ['Створення сучасного українського мистецтва', 'Участь у проведенні виставок та мистецьких заходів', 'Популяризація українських митців в усьому світі'],
                'en' => ['Creation of contemporary Ukrainian art', 'Participation in exhibitions and artistic events', 'Promotion of Ukrainian artists worldwide']
            ],
            'footer_expert_button_text' => [
                'uk' => 'Відправити заявку',
                'en' => 'Send Application'
            ],

            'is_active' => true,
            'statistics_is_active' => true,
        ];
    }
}
