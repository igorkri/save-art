<?php

namespace Database\Seeders;

use App\Models\HomePage;
use Illuminate\Database\Seeder;

class HomePageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо вже є запис
        if (HomePage::exists()) {
            return;
        }

        // Створюємо початковий запис головної сторінки
        HomePage::create([
            'hero_title' => [
                'uk' => 'Мистецтво допомоги — найсучасніше з мистецтв',
                'en' => 'The Art of Help — the Most Contemporary of Arts',
            ],
            'hero_video_poster' => 'hero-videos/hero.webp',

            'donates_subtitle' => [
                'uk' => 'ДОЛУЧАЙТЕСЬ ДО ВІДРОДЖЕННЯ ТА РОЗВИТКУ УКРАЇНСЬКОЇ КУЛЬТУРИ',
                'en' => 'JOIN THE REVIVAL AND DEVELOPMENT OF UKRAINIAN CULTURE',
            ],
            'donates_title' => [
                'uk' => 'Твоя підтримка — натхнення для митця',
                'en' => 'Your Support — Inspiration for the Artist',
            ],
            'donates_text' => [
                'uk' => 'Ми пропонуємо прозору систему донатів, яка забезпечить майбутній проєкт в будь-якій галузі мистецтва стабільною підтримкою. Донатерами можуть бути як фізичні так і юридичні особи. Навіть 10₴ допоможуть митцю реалізувати свій творчий потенціал.',
                'en' => 'We offer a transparent donation system that will provide future projects in any field of art with stable support. Donors can be both individuals and legal entities. Even ₴10 will help an artist realize their creative potential.',
            ],

            'total_collected' => 2325250,
            'declared_projects' => 624,
            'active_projects' => 387,
            'completed_projects' => 1126,
            'sold_projects' => 107,

            'partners_title' => [
                'uk' => 'Партнери',
                'en' => 'Partners',
            ],

            'ad_first_title' => [
                'uk' => 'Долучайтесь до Мистецтва Перемоги!',
                'en' => 'Join the Art of Victory!',
            ],
            'ad_first_button_text' => [
                'uk' => 'Підтримати платформу',
                'en' => 'Support the Platform',
            ],
            'ad_first_image' => 'advertising/advert1.webp',

            'ad_second_title' => [
                'uk' => 'Ваша допомога та підтримка стане світловим імпульсом відбудови сучасного ренесансу!',
                'en' => 'Your help and support will become a light impulse for the reconstruction of a modern renaissance!',
            ],
            'ad_second_button_text' => [
                'uk' => 'Підтримати митців',
                'en' => 'Support Artists',
            ],
            'ad_second_image' => 'advertising/advert2.webp',

            'footer_expert_title' => [
                'uk' => 'Запрошуємо експертів до співпраці',
                'en' => 'We Invite Experts to Cooperate',
            ],
            'footer_expert_text' => [
                'uk' => 'Благодійний фонд ID_Art UA відкритий до співпраці з експертами у галузі мистецтва, кураторами, галереями та колекціонерами.',
                'en' => 'ID_Art UA Charitable Foundation is open for cooperation with art experts, curators, galleries and collectors.',
            ],
            'footer_expert_features' => [
                'uk' => ['Створення сучасного українського мистецтва', 'Участь у проведенні виставок та мистецьких заходів', 'Популяризація українських митців в усьому світі'],
                'en' => ['Creation of contemporary Ukrainian art', 'Participation in exhibitions and artistic events', 'Promotion of Ukrainian artists worldwide'],
            ],
            'footer_expert_button_text' => [
                'uk' => 'Відправити заявку',
                'en' => 'Send Application',
            ],

            'is_active' => true,
        ]);
    }
}
