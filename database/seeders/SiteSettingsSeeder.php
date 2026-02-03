<?php

namespace Database\Seeders;

use App\Models\SiteSettings;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteSettings::create([
            // Header
            'header_brand_name' => [
                'uk' => 'save-art.in.ua',
                'en' => 'save-art.in.ua',
            ],
            'header_dropdown_sites' => [
                [
                    'name' => ['uk' => 'save-art.in.ua', 'en' => 'save-art.in.ua'],
                    'url' => 'https://save-art.in.ua',
                    'is_active' => true,
                ],
                [
                    'name' => ['uk' => 'art-ua.info', 'en' => 'art-ua.info'],
                    'url' => 'https://art-ua.info',
                    'is_active' => false,
                ],
                [
                    'name' => ['uk' => 'art-ua.com', 'en' => 'art-ua.com'],
                    'url' => 'https://art-ua.com',
                    'is_active' => false,
                ],
            ],
            'header_menu' => [
                [
                    'label' => ['uk' => 'Проєкти', 'en' => 'Projects'],
                    'url' => '/projects/page/1',
                ],
                [
                    'label' => ['uk' => 'Звіти', 'en' => 'Reports'],
                    'url' => '/reports',
                ],
                [
                    'label' => ['uk' => 'Спецпроєкти', 'en' => 'Special Projects'],
                    'url' => '/special-projects',
                ],
                [
                    'label' => ['uk' => 'Про нас', 'en' => 'About Us'],
                    'url' => '/about-us',
                ],
            ],
            'header_socials' => [
                'instagram' => 'https://instagram.com/',
                'facebook' => 'https://facebook.com/',
                'youtube' => 'https://youtube.com/',
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

            // Footer Top
            'footer_brand_name' => [
                'uk' => 'save-art.in.ua',
                'en' => 'save-art.in.ua',
            ],
            'footer_slogan' => [
                'uk' => 'Мистецтво допомоги — найсучасніше з мистецтв',
                'en' => 'The art of helping is the most modern of arts',
            ],
            'footer_collaboration_title' => [
                'uk' => 'Запрошуємо експертів до співпраці',
                'en' => 'We invite experts to collaborate',
            ],
            'footer_collaboration_text' => [
                'uk' => 'Благодійний фонд ID_Art UA відкритий до співпраці ...',
                'en' => 'The ID_Art UA Charitable Foundation is open to collaboration ...',
            ],
            'footer_collaboration_items' => [
                [
                    'image' => null,
                    'text' => ['uk' => 'Створення сучасного українського мистецтва', 'en' => 'Creating modern Ukrainian art'],
                ],
                [
                    'image' => null,
                    'text' => ['uk' => 'Участь у проведенні виставок та мистецьких заходів', 'en' => 'Participation in exhibitions and art events'],
                ],
                [
                    'image' => null,
                    'text' => ['uk' => 'Популяризація українських митців в усьому світі', 'en' => 'Promoting Ukrainian artists worldwide'],
                ],
            ],
            'footer_collaboration_button_text' => [
                'uk' => 'Відправити заявку',
                'en' => 'Send application',
            ],

            // Footer Middle - меню сайтів
            'footer_sites_menu' => [
                [
                    'site_name' => ['uk' => 'save-art.in.ua', 'en' => 'save-art.in.ua'],
                    'site_url' => '/',
                    'links' => [
                        ['label' => ['uk' => 'Проєкти', 'en' => 'Projects'], 'url' => '/projects/page/1'],
                        ['label' => ['uk' => 'Звіти', 'en' => 'Reports'], 'url' => '/reports'],
                        ['label' => ['uk' => 'Спецпроєкти', 'en' => 'Special Projects'], 'url' => '/special-projects'],
                        ['label' => ['uk' => 'Про нас', 'en' => 'About Us'], 'url' => '/about-us'],
                        ['label' => ['uk' => 'Часті питання', 'en' => 'FAQ'], 'url' => '/faq'],
                        ['label' => ['uk' => 'Умови використання', 'en' => 'Terms of Use'], 'url' => '/terms-of-use'],
                    ],
                ],
                [
                    'site_name' => ['uk' => 'art-ua.info', 'en' => 'art-ua.info'],
                    'site_url' => 'https://art-ua.info',
                    'links' => [
                        ['label' => ['uk' => 'Учасники', 'en' => 'Participants'], 'url' => '#'],
                        ['label' => ['uk' => 'Каталоги', 'en' => 'Catalogs'], 'url' => '#'],
                        ['label' => ['uk' => 'Проєкти', 'en' => 'Projects'], 'url' => '#'],
                        ['label' => ['uk' => 'Послуги', 'en' => 'Services'], 'url' => '#'],
                        ['label' => ['uk' => 'Новини та події', 'en' => 'News and Events'], 'url' => '#'],
                        ['label' => ['uk' => 'Часті питання', 'en' => 'FAQ'], 'url' => '#'],
                        ['label' => ['uk' => 'Умови використання', 'en' => 'Terms of Use'], 'url' => '/terms-of-use'],
                    ],
                ],
                [
                    'site_name' => ['uk' => 'art-ua.com', 'en' => 'art-ua.com'],
                    'site_url' => 'https://art-ua.com',
                    'links' => [
                        ['label' => ['uk' => 'Учасники', 'en' => 'Participants'], 'url' => '#'],
                        ['label' => ['uk' => 'Каталоги', 'en' => 'Catalogs'], 'url' => '#'],
                        ['label' => ['uk' => 'Проєкти', 'en' => 'Projects'], 'url' => '#'],
                        ['label' => ['uk' => 'Послуги', 'en' => 'Services'], 'url' => '#'],
                        ['label' => ['uk' => 'Новини та події', 'en' => 'News and Events'], 'url' => '#'],
                        ['label' => ['uk' => 'Часті питання', 'en' => 'FAQ'], 'url' => '#'],
                        ['label' => ['uk' => 'Умови використання', 'en' => 'Terms of Use'], 'url' => '/terms-of-use'],
                    ],
                ],
            ],

            // Footer Bottom - контактна інформація
            'footer_company_name' => [
                'uk' => 'БЛАГОДІЙНИЙ ФОНД ID_Art UA',
                'en' => 'ID_Art UA CHARITABLE FOUNDATION',
            ],
            'footer_address' => [
                'uk' => 'м. Івано-Франківськ, Україна',
                'en' => 'Ivano-Frankivsk, Ukraine',
            ],
            'footer_email' => 'idartua.bo@gmail.com',
            'footer_phone' => '+380 67 734 5938',
            'footer_social_links' => [
                [
                    'type' => 'instagram',
                    'url' => 'https://instagram.com/',
                    'label' => null,
                ],
                [
                    'type' => 'facebook',
                    'url' => 'https://facebook.com/ua.id.art',
                    'label' => 'ua.id.art',
                ],
                [
                    'type' => 'youtube',
                    'url' => 'https://youtube.com/@id_artUA',
                    'label' => '@id_artUA',
                ],
            ],
            'footer_copyright_year' => '2025',
        ]);
    }
}
