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
            'header_brand_name' => 'save-art.in.ua',
            'header_dropdown_sites' => [
                [
                    'name' => 'save-art.in.ua',
                    'url' => 'https://save-art.in.ua',
                    'is_active' => true,
                ],
                [
                    'name' => 'art-ua.info',
                    'url' => 'https://art-ua.info',
                    'is_active' => false,
                ],
                [
                    'name' => 'art-ua.com',
                    'url' => 'https://art-ua.com',
                    'is_active' => false,
                ],
            ],
            'header_menu' => [
                [
                    'label' => 'Проєкти',
                    'url' => '/projects/page/1',
                ],
                [
                    'label' => 'Звіти',
                    'url' => '/reports',
                ],
                [
                    'label' => 'Спецпроєкти',
                    'url' => '/special-projects',
                ],
                [
                    'label' => 'Про нас',
                    'url' => '/about-us',
                ],
            ],
            'header_socials' => [
                'instagram' => 'https://instagram.com/',
                'facebook' => 'https://facebook.com/',
                'youtube' => 'https://youtube.com/',
            ],
            'header_support_button_url' => '/support-platform',
            'header_support_button_text' => 'Підтримати',
            'header_login_button_text' => 'Увійти',

            // Footer Top
            'footer_brand_name' => 'save-art.in.ua',
            'footer_slogan' => 'Мистецтво допомоги — найсучасніше з мистецтв',
            'footer_collaboration_title' => 'Запрошуємо експертів до співпраці',
            'footer_collaboration_text' => 'Благодійний фонд ID_Art UA відкритий до співпраці ...',
            'footer_collaboration_items' => [
                [
                    'image' => null,
                    'text' => 'Створення сучасного українського мистецтва',
                ],
                [
                    'image' => null,
                    'text' => 'Участь у проведенні виставок та мистецьких заходів',
                ],
                [
                    'image' => null,
                    'text' => 'Популяризація українських митців в усьому світі',
                ],
            ],
            'footer_collaboration_button_text' => 'Відправити заявку',

            // Footer Middle - меню сайтів
            'footer_sites_menu' => [
                [
                    'site_name' => 'save-art.in.ua',
                    'site_url' => '/',
                    'links' => [
                        ['label' => 'Проєкти', 'url' => '/projects/page/1'],
                        ['label' => 'Звіти', 'url' => '/reports'],
                        ['label' => 'Спецпроєкти', 'url' => '/special-projects'],
                        ['label' => 'Про нас', 'url' => '/about-us'],
                        ['label' => 'Часті питання', 'url' => '/faq'],
                        ['label' => 'Умови використання', 'url' => '/terms-of-use'],
                    ],
                ],
                [
                    'site_name' => 'art-ua.info',
                    'site_url' => 'https://art-ua.info',
                    'links' => [
                        ['label' => 'Учасники', 'url' => '#'],
                        ['label' => 'Каталоги', 'url' => '#'],
                        ['label' => 'Проєкти', 'url' => '#'],
                        ['label' => 'Послуги', 'url' => '#'],
                        ['label' => 'Новини та події', 'url' => '#'],
                        ['label' => 'Часті питання', 'url' => '#'],
                        ['label' => 'Умови використання', 'url' => '/terms-of-use'],
                    ],
                ],
                [
                    'site_name' => 'art-ua.com',
                    'site_url' => 'https://art-ua.com',
                    'links' => [
                        ['label' => 'Учасники', 'url' => '#'],
                        ['label' => 'Каталоги', 'url' => '#'],
                        ['label' => 'Проєкти', 'url' => '#'],
                        ['label' => 'Послуги', 'url' => '#'],
                        ['label' => 'Новини та події', 'url' => '#'],
                        ['label' => 'Часті питання', 'url' => '#'],
                        ['label' => 'Умови використання', 'url' => '/terms-of-use'],
                    ],
                ],
            ],

            // Footer Bottom - контактна інформація
            'footer_company_name' => 'БЛАГОДІЙНИЙ ФОНД ID_Art UA',
            'footer_address' => 'м. Івано-Франківськ, Україна',
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
