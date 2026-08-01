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
            'statistics_is_active' => false, // Для тестирования установим в false

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

            'platform_description_tagline' => [
                'uk' => 'art-ua',
                'en' => 'art-ua',
            ],
            'platform_description_title' => [
                'uk' => 'Про платформу',
                'en' => 'About the Platform',
            ],
            'platform_description_subtitle' => [
                'uk' => "Спільнота підтримки\nукраїнських митців",
                'en' => "A Community Supporting\nUkrainian Artists",
            ],
            'platform_description_paragraphs' => [
                'uk' => [
                    'Ми створюємо всеукраїнську платформу особистостей з різних культурних сфер діяльності, чия професійна кваліфікація передбачає високий творчий потенціал для створення унікальних витворів мистецтва, що художньо переосмислюють сучасні історичні події та стани людства, пропагують мир, збагачують світову мистецьку спадщину.',
                    'Для подолання глобальної культурної кризи в країні, яка стоїть на варті миру у всьому світі, ми надаємо можливість формувати новітню мистецьку спадщину, яка транслює мистецтво перемоги та вбереже наступні покоління від руїн.',
                ],
                'en' => [
                    'We are building a nationwide platform for individuals from various cultural fields whose professional qualifications entail high creative potential for producing unique works of art that artistically reinterpret current historical events and the state of humanity, promote peace, and enrich the world\'s artistic heritage.',
                    'To overcome the global cultural crisis in a country standing guard over peace throughout the world, we provide the opportunity to form a new artistic heritage that conveys the art of victory and protects future generations from ruin.',
                ],
            ],
            'platform_features' => [
                'uk' => [
                    ['title' => 'Творче натхнення', 'description' => 'Досліджуй роботи інших, надихайся і вдосконалюй свої власні проєкти.'],
                    ['title' => 'Визнання та нові можливості', 'description' => 'Підвищуй свою видимість і знайди нові проєкти та співпрацю, які допоможуть тобі втілити свої творчі амбіції.'],
                    ['title' => 'Навчання у кращих', 'description' => 'Відвідуй вебінари та майстер-класи, що допоможуть тобі підвищити свої професійні навички.'],
                ],
                'en' => [
                    ['title' => 'Creative Inspiration', 'description' => 'Explore the work of others, get inspired, and improve your own projects.'],
                    ['title' => 'Recognition and New Opportunities', 'description' => 'Increase your visibility and find new projects and collaborations that will help you realize your creative ambitions.'],
                    ['title' => 'Learn from the Best', 'description' => 'Attend webinars and workshops that will help you improve your professional skills.'],
                ],
            ],

            'is_active' => true,
        ]);
    }
}
